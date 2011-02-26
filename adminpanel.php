<?php
// Fetch the options
$wpbc_statistics 	= get_option('wpbc_statistics');
$wpbc_reportstack 	= get_option('wpbc_reportstack');

?>


<div class="wrap">
	<h2>WP-BlackCheck - Settings</h2>
	<p>Welcome to the settings page of your WP-BlackCheck Plugin. You are able to configure some settings here to adapt the plugin to your needs.
	For more information visit <a href="http://my.stargazer.at/tag/wp-blackcheck/" target="_blank">this page</a>.</p>

<?php
if(isset($_POST['submitted'])) echo '<div style="border:1px outset gray; margin:.5em; padding:.5em; background-color:#efd;">Settings updated.</div>';
echo get_option('wpbc_statistics');

?>

	<h3>Settings:</h3>
	<form name="wpbc-settings" action="" method="post">
	<table cellspacing="2" cellpadding="5" class="editform" summary="WP-BlackCheck Settings" border="0">
		<tr>
			<td>Show statistics on the dashboard:</td>
			<td>&nbsp;</td>
			<td><input name="wpbc_statistics" type="checkbox" value="on" <?php if($wpbc_statistics == 'on') { echo "checked=\"checked\""; } ?> /></td>
		</tr>
		<tr>
			<td>Number of IPs to report at once:</td>
			<td>&nbsp;</td>
			<td><input name="wpbc_reportstack" type="text" size="5" maxlength="5" value="<?php echo $wpbc_reportstack; ?>"/></td>
		</tr>
		<tr>
			<td colspan="3"><small>Enter '-1' to report all the IPs at once, disabling the limit.</smalL></td>
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
</div>

