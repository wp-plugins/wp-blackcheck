<?php
/**
 * @package WP-Blackcheck
 * @author Christoph "Stargazer" Bauer
 * @version 1.4
 */
/*
Plugin Name: WP-Blackcheck
Plugin URI: http://www.stargazer.at/projects#
Description: This plugin is a simple blacklisting checker that works with our hosts
Author: Christoph "Stargazer" Bauer
Version: 1.4
Author URI: http://my.stargazer.at/

Changelog:

1.4 - Changed Spamcount before reporting, empty quarantine now supported
1.3 - If someone spams 3 times, it's most likely NOT an accident
1.2 - Remove reported spam to prevent double reports
1.1 - Added reporting
1.0 - Simple check against the centralized blacklist

*/


function do_check($request, $host, $path, $port = 80) {
        global $wp_version;
        $http_request  = "POST $path HTTP/1.0\r\n";
        $http_request .= "Host: $host\r\n";
        $http_request .= "Content-Type: application/x-www-form-urlencoded; charset=" . get_option('blog_charset') . "\r\n";
        $http_request .= "Content-Length: " . strlen($request) . "\r\n";
        $http_request .= "User-Agent: WordPress/$wp_version | CheckBlack/1.0\r\n";
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
	$userip = $comment['user_ip'];
	$querystring = 'user_ip='.$userip.'&mode=query&bloghost='.urlencode(get_option('home'));
	$response = do_check($querystring, 'www.stargazer.at', '/blacklist/query.php');
	
	if ($response[1] != "NOT LISTED") {
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

function check_akismet_queue() {
    global $wpdb;
    $comments = $wpdb->get_results("SELECT comment_author_IP, COUNT(comment_author_IP) AS comment_per_ip FROM $wpdb->comments WHERE comment_approved = 'spam' GROUP BY comment_author_IP");
    if ($comments) {
	foreach($comments as $comment) {
	    // We're checking for if someone spammed us (more) than 2 times 
	    if ($comment->comment_per_ip > 1) {
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
		// Empty the spam quarantine
		$wpdb->query("DELETE FROM $wpdb->comments WHERE comment_approved = 'spam'");
	    }
	} 
	
    } else {
	echo '<p>Nothing to report as your spam queue is empty.</p>';
    }
}


function blackcheck_report() {
?>
<div class="wrap">
  <h2>WP-BlackCheck</h2>
<?php 
  if(isset($_POST['submitted'])){
    echo '<ul>';
    check_akismet_queue();
    echo '</ul><p>Process finished.</p>';
  } else {
?> 
  <p>By pressing this button you are only reporting the spammers IP adresses. You still have to delete the spam manually!</p>
  <form name="blackcheck_form" action="" method="post">
   <p class="submit">
      <input type="hidden" name="submitted" />
      <input type="submit" name="Submit" value="<?php _e($rev_action);?> Scan Spam-Queue and report IPs &raquo;" />
   </p>
   </form>
<?php } ?>
</div>

<?php
}

function blackcheck_add_page() {
	// add_submenu_page('options-general.php', 'WP-BlackCheck', 'WP-BlackCheck', 10, __FILE__, 'blackcheck_report');
	add_submenu_page('index.php', 'WP-BlackCheck', 'WP-BlackCheck', 'manage_options', __FILE__, 'blackcheck_report');
	
}

add_action('admin_menu', 'blackcheck_add_page');
add_action('preprocess_comment', 'blackcheck', 1);
?>
