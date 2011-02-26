<?php
/**
 * @package WP-Blackcheck
 * @author Christoph "Stargazer" Bauer
 * @version 1.9
 */
/*
Plugin Name: WP-Blackcheck
Plugin URI: http://www.stargazer.at/projects#
Description: This plugin is a simple blacklisting checker that works with our hosts
Author: Christoph "Stargazer" Bauer
Version: 1.9
Author URI: http://my.stargazer.at/

    Copyright 2010 Christoph Bauer  (email : cbauer@stargazer.at)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


if ( !function_exists( 'add_action' ) ) {
	echo "Called directly. Taking the emergency exit.";
	exit;
}


function do_check($request, $host, $path, $port = 80) {
        global $wp_version;
        $http_request  = "POST $path HTTP/1.0\r\n";
        $http_request .= "Host: $host\r\n";
        $http_request .= "Content-Type: application/x-www-form-urlencoded; charset=" . get_option('blog_charset') . "\r\n";
        $http_request .= "Content-Length: " . strlen($request) . "\r\n";
        $http_request .= "User-Agent: WordPress/$wp_version | CheckBlack/1.9\r\n";
        $http_request .= "\r\n";
        $http_request .= $request;

        $response = '';
        if( false != ( $fs = @fsockopen($host, $port, $errno, $errstr, 10) ) ) {
                fwrite($fs, $http_request);

                while ( !feof($fs) )
                        $response .= fgets($fs, 1160); // One TCP-IP packet
                fclose($fs);
                $response = explode("\r\n\r\n", $response, 2);
        }


        return $response;
}

function blackcheck($comment) {
    if (!is_user_logged_in()) {
	$userip = $_SERVER['REMOTE_ADDR'];
	$querystring = 'user_ip='.$userip.'&mode=query&bloghost='.urlencode(get_option('home'));
	$response = do_check($querystring, 'www.stargazer.at', '/blacklist/query.php');
	
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

function report_spam_button($comment_status) {
        if ( 'approved' == $comment_status )
                return;

	if ( function_exists('plugins_url') )
		$link = 'index.php?page=wp-blackcheck/wp-blackcheck.php';
	echo "</div><div class='alignleft'><a class='button-secondary checkforspam' href='$link'>" . __('Report and Clean Spam') . "</a>";

}
add_action('manage_comments_nav', 'report_spam_button');

function blackcheck_stats() {
	if ( get_option('wpbc_statistics') == 'on' ) {
		if ( !$count = get_option('blackcheck_spam_count') )
			return;
		echo '<p>'.sprintf( _n( '<a href="%1$s">WP-BlackCheck</a> has protected your site from <strong>%2$s</strong> spam comments.','<a href="%1$s">WP-BlackCheck</a> has protected your site from <strong>%2$s</strong> spam comments.', $count ), 'http://www.stargazer.at/blacklist/', number_format_i18n($count) ).'</p>';
	}
}
add_action('activity_box_end', 'blackcheck_stats');

function check_akismet_queue($limit='-1') {
    global $wpdb;
    if (!is_numeric($limit)) $limit = '-1';
    if ($limit == -1) {
      $comments = $wpdb->get_results("SELECT comment_author_IP FROM $wpdb->comments WHERE comment_approved = 'spam' GROUP BY comment_author_IP");
    } else {
      $comments = $wpdb->get_results("SELECT comment_author_IP FROM $wpdb->comments WHERE comment_approved = 'spam' GROUP BY comment_author_IP LIMIT $limit");
    }

    if ($comments) {
	foreach($comments as $comment) {
	    $userip = $comment->comment_author_IP;
	    // prevent reporting listed hosts
	    $querystring = 'user_ip='.$userip.'&mode=query&bloghost='.urlencode(get_option('home'));
	    $response = do_check($querystring, 'www.stargazer.at', '/blacklist/query.php');
	    // found someone new?
	    if ($response[1] == "NOT LISTED") {
		$querystring = 'user_ip='.$userip.'&mode=report&bloghost='.urlencode(get_option('home'));
		$response = do_check($querystring, 'www.stargazer.at', '/blacklist/query.php');
		echo '<li>Reported new: '.$userip.'</li>';
	    } else {
		echo '<li>Already known: '.$userip.'</li>';
	    }
	    // Purge IP from the spam quarantine
	    $wpdb->query("DELETE FROM $wpdb->comments WHERE comment_approved = 'spam' AND comment_author_IP = '$userip'"); 	    
	}
        $comments = $wpdb->get_results("SELECT comment_author_IP FROM $wpdb->comments WHERE comment_approved = 'spam'");
        if ($comments) echo '<p>There are still some spam comments in your queue. Click <a href="index.php?page=wp-blackcheck/wp-blackcheck.php">here</a> to process the next batch.</p>';

    } else {
	echo '<p>Nothing to report. Your spam queue is empty.</p>';
    }
}

function blackcheck_report($param) {
    echo '<div class="wrap"><h2>WP-BlackCheck</h2>';
    echo '<ul>';
    check_akismet_queue(get_option('wpbc_reportstack', '-1'));
    echo '</ul><p>Process finished.</p>';
    echo '</div>';
}

function blackcheck_add_page() {
	add_submenu_page('index.php', 'WP-BlackCheck', 'Report Spam', 'manage_options', __FILE__, 'blackcheck_report');
	add_submenu_page('options-general.php', 'WP-BlackCheck', 'WP-BlackCheck', 10, __FILE__, 'do_adminpage');
	
}

function wpbc_install() {
	if ( !get_option('wpbc_stacksize') ) {
		update_option('wpbc_statistics',  'on');
		update_option('wpbc_reportstack', '-1');
	}
}

function do_adminpage() {
	if(isset($_POST['submitted'])) {
		update_option('wpbc_statistics', $_POST['wpbc_statistics']);
		if ($_POST['wpbc_reportstack']) update_option('wpbc_reportstack', $_POST['wpbc_reportstack']);
		
	}
	
	include('adminpanel.php');
}

add_action('activate_wp-blackcheck/wp-blackcheck.php', 'wpbc_install');
add_action('admin_menu', 'blackcheck_add_page');
add_action('preprocess_comment', 'blackcheck', 1);
?>
