<?php
// Securing against direct calls
if (!defined('ABSPATH')) die("Called directly. Taking the emergency exit.");

// Fetch the options
$wpbc_statistics 		= get_option('wpbc_statistics');
$wpbc_reportstack 		= get_option('wpbc_reportstack');
$wpbc_ip_already_spam		= get_option('wpbc_ip_already_spam');
$wpbc_nobbcode			= get_option('wpbc_nobbcode');
$wpbc_nobbcode_autoreport	= get_option('wpbc_nobbcode_autoreport');
$wpbc_timecheck			= get_option('wpbc_timecheck');
$wpbc_timecheck_autoreport	= get_option('wpbc_timecheck_autoreport');
$wpbc_linklimit			= get_option('wpbc_linklimit');
$wpbc_linklimit_number		= get_option('wpbc_linklimit_number');
$wpbc_trackback_list		= get_option('wpbc_trackback_list');
$wpbc_trackback_check		= get_option('wpbc_trackback_check');
?>


<div class="wrap">
	<h2>WP-BlackCheck - Settings</h2>
	<p>Welcome to the settings page of your WP-BlackCheck Plugin. You are able to configure some settings here to adapt the plugin to your needs.<br />
	For more information visit <a href="http://my.stargazer.at/tag/wp-blackcheck/" target="_blank">this page</a>.</p>

<?php
if(isset($_POST['submitted'])) echo '<div style="border:1px outset gray; margin:.5em; padding:.5em; background-color:#efd;">Settings updated.</div>';
?>

	<h3>Settings:</h3>
	<form name="wpbc-settings" action="" method="post">
	<table cellspacing="2" cellpadding="5" class="editform" summary="WP-BlackCheck Settings" border="0">
		<tr height="30px">
			<td colspan="3"><strong>Blacklist settings:</strong></td>
		</tr>
		<tr>
			<td>Number of IPs to report at once:</td>
			<td>&nbsp;</td>
			<td><input name="wpbc_reportstack" type="text" size="5" maxlength="5" value="<?php echo $wpbc_reportstack; ?>"/></td>
		</tr>
		<tr>
			<td colspan="3"><small>Enter '-1' to report all the IPs at once, disabling the limit.</smalL></td>
		</tr>
		
		<tr height="30px">
			<td colspan="3"><strong>Misc Spam prevention functions:</strong></td>
		</tr>
		<tr>
			<td>Throttle spammers having 3 comments in your queue:</td>
			<td>&nbsp;</td>
			<td><input name="wpbc_ip_already_spam" type="checkbox" value="on" <?php if($wpbc_ip_already_spam == 'on') { echo "checked=\"checked\""; } ?> /></td>
		</tr>
		
		<tr>
			<td>Do not accept bbCode-Links:</td>
			<td>&nbsp;</td>
			<td><input name="wpbc_nobbcode" type="checkbox" value="on" <?php if($wpbc_nobbcode == 'on') { echo "checked=\"checked\""; } ?> /></td>
		</tr>
		
		<?php
		if ($wpbc_nobbcode) {
		?>
		<tr>
			<td>Automatically report IPs that try to send bbCode-Links:</td>
			<td>&nbsp;</td>
			<td><input name="wpbc_nobbcode_autoreport" type="checkbox" value="on" <?php if($wpbc_nobbcode_autoreport == 'on') { echo "checked=\"checked\""; } ?> /></td>
		</tr>
		<?php
		}
		?>
		<tr>
			<td>Use speed-limit for comments (comment typing needs more than 5 sec):</td>
			<td>&nbsp;</td>
			<td><input name="wpbc_timecheck" type="checkbox" value="on" <?php if($wpbc_timecheck == 'on') { echo "checked=\"checked\""; } ?> /></td>
		</tr>
		
		<?php
		if ($wpbc_timecheck) {
		?>
		<tr>
			<td>Automatically report IPs that break speed-limits:</td>
			<td>&nbsp;</td>
			<td><input name="wpbc_timecheck_autoreport" type="checkbox" value="on" <?php if($wpbc_timecheck_autoreport == 'on') { echo "checked=\"checked\""; } ?> /></td>
		</tr>
		<?php
		}
		?>
		
		<tr>
			<td>Block comments having too many links:</td>
			<td>&nbsp;</td>
			<td><input name="wpbc_linklimit" type="checkbox" value="on" <?php if($wpbc_linklimit == 'on') { echo "checked=\"checked\""; } ?> /></td>
		</tr>
		<?php
		if ($wpbc_linklimit) {
		?>
		<tr>
			<td>Maximum number of links:</td>
			<td>&nbsp;</td>
			<td><input name="wpbc_linklimit_number" type="text" size="5" maxlength="2" value="<?php echo $wpbc_linklimit_number; ?>"/></td>
		</tr>
		<?php
		}
		?>
		<tr height="30px">
			<td colspan="3"><strong>Pingback / Trackback Settings:</strong></td>
		</tr>
		<tr>
			<td>Check Trackbacks against Blacklist (<i>not recommended</i>):</td>
			<td>&nbsp;</td>
			<td><input name="wpbc_trackback_list" type="checkbox" value="on" <?php if($wpbc_trackback_list == 'on') { echo "checked=\"checked\""; } ?> /></td>
		</tr>
		<tr>
			<td>Validate Trackbacks</td>
			<td>&nbsp;</td>
			<td><input name="wpbc_trackback_check" type="checkbox" value="on" <?php if($wpbc_trackback_check == 'on') { echo "checked=\"checked\""; } ?> /></td>
		</tr>
		
		<tr height="30px">
			<td colspan="3"><strong>Statistics:</strong></td>
		</tr>
		<tr>
			<td>Show statistics on the dashboard:</td>
			<td>&nbsp;</td>
			<td><input name="wpbc_statistics" type="checkbox" value="on" <?php if($wpbc_statistics == 'on') { echo "checked=\"checked\""; } ?> /></td>
		</tr>
		<tr>
			<td>Reset WP-BlackCheck stats (<?php echo get_option('blackcheck_spam_count'); ?>):</td>
			<td>&nbsp;</td>
			<td><input name="wpbc_clear_wpbc_stats" type="checkbox" value="on" /></td>
		</tr>
		<tr>
			<td>Reset Akismet stats (<?php echo get_option('akismet_spam_count'); ?>):</td>
			<td>&nbsp;</td>
			<td><input name="wpbc_clear_akismet_stats" type="checkbox" value="on" /></td>
		</tr>
		<tr>
			<td align="right" colspan="3">
				<div class="submit"><input type="hidden" name="submitted" /><input type="submit" name="Submit" value="<?php _e($rev_action);?> Update Settings &raquo;" /></div>
			</td>
		</tr>
	</table>
	</form>
	
	<h3>Known problems:</h3>
	<p>
		<strong>Q:</strong> If the number of messages in the Spam-Queue is very high, the script times out.<br />
		<strong>A:</strong> Decrease the number of IPs being reported at once. The number you are reporting at once depends on your hosting environment.
	</p>
	<p>
		<strong>Q:</strong> Trackbacks do not work since WP-BlackCheck checks them.<br />
		<strong>A:</strong> As some blogs live on hosted environments it might have happened that the server got listed.
	</p>
</div>