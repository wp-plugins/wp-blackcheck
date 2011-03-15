<?php
/**
 * @package WP-Blackcheck
 * @author Christoph "Stargazer" Bauer
 * @version 1.12
 */
/*
Plugin Name: WP-Blackcheck
Plugin URI: http://www.stargazer.at/projects#
Description: This plugin is a simple blacklisting checker that works with our hosts
Author: Christoph "Stargazer" Bauer
Version: 1.12
Author URI: http://my.stargazer.at/

    Copyright 2010 Christoph Bauer  (email : cbauer@stargazer.at)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

*/

// Securing against direct calls
if (!defined('ABSPATH')) die("Called directly. Taking the emergency exit.");

include ('functions.inc.php');


// Check an IP
function do_check($userip) {
	$querystring = 'user_ip='.$userip.'&mode=query&bloghost='.urlencode(get_option('home'));
	$response = do_request($querystring, 'www.stargazer.at', '/blacklist/query.php');
	return $response;
}

// Report an IP
function do_report($userip) {
	$querystring = 'user_ip='.$userip.'&mode=report&bloghost='.urlencode(get_option('home'));
	$response = do_request($querystring, 'www.stargazer.at', '/blacklist/query.php');
	return $response;
}

// Checking a comment as we got it (hook calls us)
function blackcheck($comment) {
	// IPv6 - IPv4 compatibility mode hack
	$_SERVER['REMOTE_ADDR'] = preg_replace("/^::ffff:/", "", $_SERVER['REMOTE_ADDR']);
	$userip = $_SERVER['REMOTE_ADDR'];
	
	
	// trackbacks/pingbacks are a different topic
	if ($comment['comment_type'] == 'trackback' || $comment['comment_type'] == 'pingback') {
		// trackback verification happens here
		if (get_option('wpbc_trackback_check')) {
			// Proxy servers do not send trackbacks
			$headers = get_http_headers();
			if (array_key_exists('Via', $headers) || array_key_exists('Max-Forwards', $headers) || array_key_exists('X-Forwarded-For', $headers) || array_key_exists('Client-Ip', $headers)) {
				wp_die("Invalid request: Proxy servers do not send trackbacks or pingbacks.");
			}
			
			// Proper URL?
			if(!preg_match("/^http/", $comment['comment_author_url'])) {
				wp_die("Invalid url: ".$comment['comment_author_url']);
			}
			
		}
		
		if (get_option('wpbc_trackback_list')) {
			$response = do_check($userip);
			if ($response[1] != "NOT LISTED") wp_die("Your host is blacklisted and cannot send any trackbacks");
		}
		
		return $comment;
		
	}
	
		
	if (!is_user_logged_in()) {
		
		
		// Additional checks happen here as needed/wanted
		if (get_option('wpbc_ip_already_spam')) 	pc_already_spam($userip);
		if (get_option('wpbc_nobbcode')) 		pc_nobbcode($comment);
		if (get_option('wpbc_linklimit')) 		pc_linklimit($comment);
		if (get_option('wpbc_timecheck')) 		pc_speedlimit($comment);
		
		// do the blacklist-check now
		$response = do_check($userip);
		
		if ($response[1] != "NOT LISTED") {
			update_option( 'blackcheck_spam_count', get_option('blackcheck_spam_count') + 1 );
			$diemsg  = '<h1>Your host is ' . $response[1] . "</h1>\n<br />";
			$diemsg .= 'See <a href="http://www.stargazer.at/blacklist/?ip='.urlencode($userip).'">here</a> for details.';
			wp_die($diemsg);
		} else {
			return $comment;
		}
	} else {
		return $comment;
	}
}


// PreCheck - Do we know that IP from our SpamQueue already during the last 24 hours?
function pc_already_spam($userip) {
	global $wpdb;
	// if the spammer already left a few, slow him down
	$comments = $wpdb->get_results("SELECT count(comment_author_IP) as hitcount FROM $wpdb->comments WHERE comment_approved = 'spam' AND comment_author_IP = '$userip' AND comment_date > DATE_SUB( now(), INTERVAL 1 DAY");
	$hitcount = $comments->hitcount;
	if ($hitcount > 2) {
		update_option( 'blackcheck_spam_count', get_option('blackcheck_spam_count') + 1 );
		// we already have his spam at least 3 times - so let's just die.
		wp_die('You have already submitted too many comments at once. Please wait before posting the next comment.');
	}
}

// PreCheck - Decline bbCode
function pc_nobbcode($comment) {
	if (preg_match('|\[url(\=.*?)?\]|is', $comment['comment_content'])) {
		if ( get_option('wpbc_nobbcode_autoreport') ) {
			$userip = $comment->comment_author_IP;
			$response = do_check($userip);
			if ($response[1] == "NOT LISTED") $response = do_report($userip);
		}
		update_option( 'blackcheck_spam_count', get_option('blackcheck_spam_count') + 1 );
		wp_die('Your comment was rejected because it contains <a href="http://en.wikipedia.org/wiki/BBCode">BBCode</a>. This blog does not use BBCode.');
	}
}

// PreCheck - Speed-Limit
function pc_speedlimit($comment) {
	$time = explode(" ", microtime()); 
	$time = $time[1] + $time[0]; 
	$finish = $time; 
	$totaltime = ($finish - $comment->comment_timestamp); 
	
	// 5 seconds from page load to submission is no time.
	if ($totaltime < 5) {
		update_option( 'blackcheck_spam_count', get_option('blackcheck_spam_count') + 1 );
		if (get_option('wpbc_timecheck_autoreport')) {
			$userip = $comment->comment_author_IP;
			$response = do_check($userip);
			if ($response[1] == "NOT LISTED") $response = do_report($userip);
		}
		wp_die("Slow down, cowboy! Speed kills.");
	}
}

// PreCheck - Link Limits
function pc_linklimit($comment) {
	$linklimit = get_option('wpbc_linklimit_number');
	$linkCount = preg_match_all("|(href\t*?=\t*?['\"]?)?(https?:)?//|i", $comment['comment_content'], $out);
	if ($linkCount > $linklimit) {
		wp_die("This blog has a limit of $linklimit hyperlinks per comment.");
	}
}


// Report-Spam button for the Spam-Queue
function report_spam_button($comment_status) {
        if ( 'approved' == $comment_status )
                return;

	if ( function_exists('plugins_url') )
		$link = 'index.php?page=wp-blackcheck/wp-blackcheck.php';
	echo "</div><div class='alignleft'><a class='button-secondary checkforspam' href='$link'>" . __('Report and Clean Spam') . "</a>";

}

// Statistics for the admin dashboard
function blackcheck_stats() {
	if ( get_option('wpbc_statistics') == 'on' ) {
		if ( !$count = get_option('blackcheck_spam_count') )
			return;
		echo '<p>'.sprintf( _n( '<a href="%1$s">WP-BlackCheck</a> has protected your site from <strong>%2$s</strong> spam comments.','<a href="%1$s">WP-BlackCheck</a> has protected your site from <strong>%2$s</strong> spam comments.', $count ), 'http://www.stargazer.at/blacklist/', number_format_i18n($count) ).'</p>';
	}
}



// Trigger for the reporting
function blackcheck_report($param) {
    echo '<div class="wrap"><h2>WP-BlackCheck</h2>';
    echo '<ul>';
    check_akismet_queue(get_option('wpbc_reportstack', '-1'));
    echo '</ul><p>Process finished.</p>';
    echo '</div>';
}

// Add our pages
function blackcheck_add_page() {
	add_submenu_page('index.php', 'WP-BlackCheck', 'Report Spam', 'manage_options', __FILE__, 'blackcheck_report');
	add_submenu_page('options-general.php', 'WP-BlackCheck', 'WP-BlackCheck', 10, __FILE__, 'do_adminpage');
	
}

// extend the comment form - we want to know more
function do_extend_commentform() {
	if ( get_option('wpbc_timecheck')) {
		$time = explode(" ", microtime()); 
		$time = $time[1] + $time[0]; 
		echo '<p style="display: none;"><input type="hidden" id="comment_timestamp" name="comment_timestamp" value="' . $time . '" /></p>';
	}
}

// Call for the admin page - page actually in adminpanel.php
function do_adminpage() {
	global $wp_db_version;
	
	if (function_exists('current_user_can')) {
		// Hello WP 2.x+
		if (current_user_can('manage_options')) {
			include('adminpanel.php');
		}
	}
}

// Action hooks here
add_action('activity_box_end', 'blackcheck_stats');
add_action('activate_wp-blackcheck/wp-blackcheck.php', 'wpbc_install');
add_action('admin_menu', 'blackcheck_add_page');
add_action('comment_form', 'do_extend_commentform');
add_action('manage_comments_nav', 'report_spam_button');
add_action('preprocess_comment', 'blackcheck', 1);
?>