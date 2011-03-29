<?php
/**
 * @package WP-BlackCheck-Functions
 * @author Christoph "Stargazer" Bauer
 * @version 2.1.0
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
				echo '<li>' . __('Reported new:', 'wp-blackcheck') . ' ' .$userip.'</li>';
			} else {
				echo '<li>' . __('Already known:', 'wp-blackcheck') . ' ' .$userip.'</li>';
			}
			// Purge IP from the spam quarantine
			$wpdb->query("DELETE FROM $wpdb->comments WHERE comment_approved = 'spam' AND comment_author_IP = '$userip'"); 	    
		}
		$comments = $wpdb->get_results("SELECT comment_author_IP FROM $wpdb->comments WHERE comment_approved = 'spam'");
		if ($comments)  echo '<p>' . __('There are still some spam comments in your queue. Click <a href="index.php?page=wp-blackcheck/wp-blackcheck.php">here</a> to process the next batch.', 'wp-blackcheck') . '</p>';
		
	} else {
		echo '<p>' . __('Nothing to report. Your spam queue is empty.', 'wp-blackcheck') . '</p>';
	}
}

// Doing the check - request
function do_request($request, $host, $path, $port = 80) {
	global $wp_version;

	if ( function_exists( 'wp_remote_post' ) ) {
		$http_args = array(
			'body'			=> $request,
			'headers'		=> array(
			'Content-Type'		=> 'application/x-www-form-urlencoded; ' . 'charset=' . get_option( 'blog_charset' ),
			'Host'			=> $host,
			'User-Agent'		=> "WordPress/$wp_version | CheckBlack/" . WPBC_VERSION,
		     ),
		     'httpversion'	=> '1.0',
		     'timeout'		=> 15
		);
		$myurl = 'http://' . $host . $path;
		
		$response = wp_remote_post( $myurl, $http_args );

		if ( is_wp_error( $response ) )
			return '';
		
		return array( $response['headers'], $response['body'] );
		
	} else {

		$http_request  = "POST $path HTTP/1.0\r\n";
		$http_request .= "Host: $host\r\n";
		$http_request .= "Content-Type: application/x-www-form-urlencoded; charset=" . get_option('blog_charset') . "\r\n";
		$http_request .= "Content-Length: " . strlen($request) . "\r\n";
		$http_request .= "User-Agent: WordPress/$wp_version | CheckBlack/" . WPBC_VERSION . "\r\n";
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

function wpbc_plugin_action_links( $links, $file ) {
	if ( $file == plugin_basename( dirname(__FILE__).'/wp-blackcheck.php' ) ) {
		$links[] = '<a href="options-general.php?page=wp-blackcheck/wp-blackcheck.php">'.__('Settings').'</a>';
	}
	
	return $links;
}

// Installer - Option handling
function wpbc_install() {
	if ( !get_option('wpbc_stacksize') ) {
		update_option('wpbc_statistics',		'on');
		update_option('wpbc_reportstack', 		'100');
		update_option('wpbc_ip_already_spam', 		'on');
		update_option('wpbc_nobbcode', 			'');
		update_option('wpbc_nobbcode_autoreport',	'');
		update_option('wpbc_timecheck', 		'on');
		update_option('wpbc_timecheck_time',		'10');
		update_option('wpbc_timecheck_autoreport',	'');
		update_option('wpbc_linklimit',			'');
		update_option('wpbc_linklimit_number',		'2');
		update_option('wpbc_trackback_list', 		'');
		update_option('wpbc_trackback_check', 		'on');
	}
}

function wpbc_reset() {
	update_option('wpbc_statistics',		'on');
	update_option('wpbc_reportstack', 		'100');
	update_option('wpbc_ip_already_spam', 		'on');
	update_option('wpbc_nobbcode', 			'');
	update_option('wpbc_nobbcode_autoreport',	'');
	update_option('wpbc_timecheck', 		'on');
	update_option('wpbc_timecheck_time',		'10');
	update_option('wpbc_timecheck_autoreport',	'');
	update_option('wpbc_linklimit',			'');
	update_option('wpbc_linklimit_number',		'2');
	update_option('wpbc_trackback_list', 		'');
	update_option('wpbc_trackback_check', 		'on');
}

function wpbc_textdomain() {
	if (function_exists('load_plugin_textdomain')) {
		if ( !defined('WP_PLUGIN_DIR') ) {
			load_plugin_textdomain('wp-blackcheck', str_replace( ABSPATH, '', dirname(__FILE__) ) . '/languages');

		} else {
			load_plugin_textdomain('wp-blackcheck', false, dirname( plugin_basename(__FILE__) ) . '/languages');
		}
		
	}
}
?>