<?php
/**
 * @package WP-Blackcheck-Functions
 * @author Christoph "Stargazer" Bauer
 * @version 1.12
 */
/*
 Function library used with WP-BlackCheck
 
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

// Actual reporting happens here
//- we loop through the comments
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
			$response = do_check($userip);
			// found someone new?
			if ($response[1] == "NOT LISTED") {
				$response = do_report($userip);
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

// Doing the check - request
function do_request($request, $host, $path, $port = 80) {
	global $wp_version;
	$http_request  = "POST $path HTTP/1.0\r\n";
	$http_request .= "Host: $host\r\n";
	$http_request .= "Content-Type: application/x-www-form-urlencoded; charset=" . get_option('blog_charset') . "\r\n";
	$http_request .= "Content-Length: " . strlen($request) . "\r\n";
	$http_request .= "User-Agent: WordPress/$wp_version | CheckBlack/1.12\r\n";
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

function ucase_all($string) {                                                                                                                                                                                                                                                     
	$temp = preg_split('/(\W)/', str_replace("_", "-", $string), -1, PREG_SPLIT_DELIM_CAPTURE);                                                                                                                                                                            
	foreach ($temp as $key=>$word) {                                                                                                                                                                                                                                       
		$temp[$key] = ucfirst(strtolower($word));                                                                                                                                                                                                                      
	}                                                                                                                                                                                                                                                                      
	return join ('', $temp);                                                                                                                                                                                                                                               
}     

// Get a usable HTTP Header (all in caps)
function get_http_headers() {
	$headers = array();
	foreach ($_SERVER as $h => $v)
		if (preg_match('/HTTP_(.+)/', $h, $hp))
			$headers[str_replace("_", "-", ucase_all($hp[1]))] = $v;
		return $headers;
}

// Installer - Option handling
function wpbc_install() {
	if ( !get_option('wpbc_stacksize') ) {
		update_option('wpbc_statistics',		'on');
		update_option('wpbc_reportstack', 		'100');
		update_option('wpbc_ip_already_spam', 		'');
		update_option('wpbc_nobbcode', 			'');
		update_option('wpbc_nobbcode_autoreport',	'');
		update_option('wpbc_timecheck', 		'');
		update_option('wpbc_timecheck_autoreport',	'');
		update_option('wpbc_linklimit',			'');
		update_option('wpbc_linklimit_number',		'5');
		update_option('wpbc_trackback_list', 		'');
		update_option('wpbc_trackback_check', 		'');
	}
}


?>