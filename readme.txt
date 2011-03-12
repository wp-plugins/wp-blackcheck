=== WP-BlackCheck ===
Contributors: Stargazer
Donate link: http://my.stargazer.at/wishlist/
Tags: comments,spam,blacklist
Requires at least: 3.0
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
1. Upload the contents of the wp-blackcheck directory into your wordpress plugin directory or use the plugin installer.
2. Activate the Plugin
3. Optionally report spam

== Frequently Asked Questions ==
= What happens if a blocked IP hits my site? =
The Plugin does not prevent reading of your blog. But if a blocked IP tries to post a comment
WP-BlackCheck will block it, displaying a link to the removal request form.

= What happens if I am blacklisted? =
I'd assume that spammers do not authenticate against your blog. The plugin will just act if
you are not logged in

= I am blacklisted! What now? =
Just follow the link to http://www.stargazer.at/blacklist/ and follow the instructions

= Why should I report spam? =
Spammers usually hit quite a few servers with their IPs. It's pretty uncommon that you are the only one who has been hit from that IP. Sharing that info prevents spam on other blogs.

== Changelog ==

= 1.11 =
* Add Link-Limit
* Add explicit trackback/pingback check for proxy servers
* Add check for valid trackback/pingback URL

= 1.10.1 =
bugfix release to fix whitespace issue and typo in request

= 1.10 =
* Code cleanup and inline documentation
* Fix direct access to adminpage
* Check permissions before doing the admin page
* Set report stacks to 100 IPs at once
* Fix issues with IPv6
* Add throttle for spammers hitting the server quite heavy
* Add option to decline bbCode and optionally report them automatically
* Add option to decline comments that come in too fast and optionally report them automatically 
* Exclude pingbacks/trackbacks from our checks

= 1.9 =
* Add admin-page
* Add reporting in chunks
* Make statistics optional

= 1.8 =
* Remove multiple spam-comment per IP check
* Fix spam deletion 
* Prepare limit for reporting in chunks

= 1.7 =
* Tighten Security
* Add statistics

= 1.6 =
* Integrated Report Button into comments view

= 1.5 =
* Corrected messages
* Fixed comment IP querying

= 1.4 =
* Changed Spamcount before reporting
* Empty quarantine now supported

= 1.3 = 
* If someone spams 3 times, it's most likely NOT an accident

= 1.2 = 
* Remove reported spam to prevent double reports

= 1.1 = 
* Add reporting

= 1.0 = 
* Simple check against the centralized blacklist
