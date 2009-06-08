=== WP-BlackCheck ===
Contributors: Stargazer
Donate link: http://my.stargazer.at/wishlist/
Tags: comment,spam,blacklist
Requires at least: 2.6
Tested up to: latest svn
Stable tag: trunk

WP-BlackCheck is a Blacklist Service for Wordpress blogs trying to block common spam IPs.

== Description ==
WP-BlackCheck is an attempt to reduce spam in the comment queue of my blogs based on the
fact that it is only a couple of IPs trying to flood my sites with spam.

Blocking them via htaccess or other server settings would do the job, but that would require
to keep all the files in sync. So I decided to write a centralized solution that enables
innocent users to remove their IP from the blacklist via removal request.

== Requirements ==
* Wordpress >= 2.6
* Akismet

== Features ==
* Prevent known IPs from posting comments
* Scan your spam comments for reporting
* Easy installation

== Installation ==
1. Upload the contents of the wp-blackcheck directory into your wordpress plugin directory.
2. Activate the Plugin
3. Optionally report spam

== Frequently Asked Questions ==
= What happens if a blocked IP hits my site? =
The Plugin does not prevent reading of your blog. But if a blocked IP tries to post a comment
WP-BlackCheck will block it, displaying a link to the removal request form.

== Screenshots ==
none


