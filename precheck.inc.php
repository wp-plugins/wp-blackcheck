<?php
/**
 * @package WP-BlackCheck-PreChecks
 * @author Christoph "Stargazer" Bauer
 * @version 2.4.0
 */
/*
 * Function library used with WP-BlackCheck
 *
 * Copyright 2011 Christoph Bauer  (email : cbauer@stargazer.at)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 */

// Securing against direct calls
if (!defined('ABSPATH')) die("Called directly. Taking the emergency exit.");

// PreCheck - Do we know that IP from our SpamQueue already during the last 24 hours?
function wpbc_pc_already_spam($userip) {
	global $wpdb;
	// if the spammer already left a few, slow him down
	$hitcount = $wpdb->get_var("SELECT count(comment_author_IP) as hitcount FROM $wpdb->comments WHERE comment_approved = 'spam' AND comment_author_IP = '$userip' AND comment_date > DATE_SUB( now(), INTERVAL 1 DAY");
	if ($hitcount > 2) {
		update_option( 'blackcheck_spam_count', get_option('blackcheck_spam_count') + 1 );
		// we already have his spam at least 3 times - so let's just die.
		wp_die( __('You have already submitted too many comments at once. Please wait before posting the next comment.', 'wp-blackcheck') );
	}
}

// PreCheck - Decline bbCode
function wpbc_pc_nobbcode($comment) {
	if (preg_match('|\[url(\=.*?)?\]|is', $comment['comment_content'])) {
		if ( get_option('wpbc_nobbcode_autoreport') ) $response = wpbc_do_report($comment->comment_author_IP);
		update_option( 'blackcheck_spam_count', get_option('blackcheck_spam_count') + 1 );
		wp_die( __('Your comment was rejected because it contains <a href="http://en.wikipedia.org/wiki/BBCode">BBCode</a>. This blog does not use BBCode.', 'wp-blackcheck') );
	}
}

// PreCheck - Speed-Limit
function wpbc_pc_speedlimit($comment) {
	if ( isset( $_POST['comment_timestamp'] )) {
		$start = base64_decode($_POST['comment_timestamp'], true);
	} else {
		// The bot could have messed with our form field.
		if (get_option('wpbc_timecheck_autoreport')) $response = wpbc_do_report($comment->comment_author_IP);
		update_option( 'blackcheck_spam_count', get_option('blackcheck_spam_count') + 1 );
		wp_die( __('Slow down, cowboy! Speed kills.', 'wp-blackcheck') );
	}

	// Someone did change our form field for sure.
	if (!is_numeric($start)) {
		if (get_option('wpbc_timecheck_autoreport')) $response = wpbc_do_report($comment->comment_author_IP);
		update_option( 'blackcheck_spam_count', get_option('blackcheck_spam_count') + 1 );
		wp_die( __('Slow down, cowboy! Speed kills.', 'wp-blackcheck') );
	}

	$finish = $_SERVER['REQUEST_TIME'];
	$totaltime = ($finish - $start);
	$charnum = strlen($comment['comment_content']);


	// Let's assume a good typer does 6 keystrokes per second...
	if ($totaltime < ($charnum / 6) ) {
		update_option( 'blackcheck_spam_count', get_option('blackcheck_spam_count') + 1 );
		if (get_option('wpbc_timecheck_autoreport')) $response = wpbc_do_report($comment->comment_author_IP);
		update_option( 'blackcheck_spam_count', get_option('blackcheck_spam_count') + 1 );

		if(WPBC_LOGFILE != ''){
			$log = fopen(WPBC_LOGFILE, 'a');
			fwrite($log, date('c') . " - BLOCKD fast comment from ".$_SERVER['REMOTE_ADDR']. " took " . $totaltime . " seconds instead of more than " . $charnum / 6 . ". (start: " . $start . " end: " . $finish . ")" .PHP_EOL);
		}

		wp_die( __('Slow down, cowboy! Speed kills.', 'wp-blackcheck') );
	}

	// Speed limit into the other direction
	if ($totaltime > ($charnum * 20)) {
		if(WPBC_LOGFILE != ''){
                	$log = fopen(WPBC_LOGFILE, 'a');
                	fwrite($log, date('c') . " - SLOW comment from ".$_SERVER['REMOTE_ADDR']. " took " . $totaltime . " seconds. Content Length: " . $charnum / 6 . " (start: " . $start . " end: " . $finish . ")" .PHP_EOL);
        	}
		// wp_die( __('Slow down, cowboy! Speed kills.', 'wp-blackcheck') );
	}

}

// PreCheck - Link Limits
function wpbc_pc_linklimit($comment) {
	$linklimit = get_option('wpbc_linklimit_number');
	$linkCount = preg_match_all("|(href\t*?=\t*?['\"]?)?(https?:)?//|i", $comment['comment_content'], $out);
	if ($linkCount > $linklimit) {
		update_option( 'blackcheck_spam_count', get_option('blackcheck_spam_count') + 1 );
		wp_die( sprintf( __("This blog has a limit of %d hyperlinks per comment.", 'wp-blackcheck'), $linklimit));
	}
}
?>
