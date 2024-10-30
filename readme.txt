=== KC S2M+MC ===
Contributors: krumch
Donate link: http://krumch.com/2012/08/24/kc-bidirectional-integration-of-s2member-with-mailchimp/
Tags: bidirectional, transparent, integration, integrate, synchronization, synchronize, s2m, s2member, mailchimp, mc, member, members, members info, mail, email, mail info, list, lists, mailing list, tool, bridge
Requires at least: 3.0
Tested up to: 5.2.4
Stable tag: 20150705
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Bidirectional transparent integration/synchronization/bridge of S2Member with MailChimp.

== Description ==

<p>Do you need a tool to make sure your MailChimp® list and your s2Member® list are synchronized? Do you want to make sure that any change by the user on his MailChimp® account is automatically changed in your s2Member® one too? Do you want to let users register through a MailChimp® sign up form and automatically be added to your s2Member® list?</p>
<p>"KC bidirectional integration of s2Member® with MailChimp®" (or just "KC S2M+MC") does exactly what it says, literally. You just have to set your MailChimp® account and fill the necessary fields in your s2Member® admin panel. Then install the KC S2M+MC plugin. It will keep your lists synchronized with your site’s members.</p>
<p>S2Member® is a great plugin. MailChimp® is a great service too. But they can not "communicate" very well without this plugin. In fact, you would need to manually check and edit your lists to synchronize them, which is not effective and not fun at all. And missing one member or one user could mean loss of sales. This won’t be necessary anymore.</p>
<p>The plugin will also detect if you don’t have s2Member® installed or if you haven’t entered your MailChimp® API key, or did not set (or change) your lists, or if you reinstall s2Member® and so on. It monitors for your activity and when you have done all the proper settings, it will run and do its synchronizing jobs. Once installed, it is a silent and trustworthy worker.
</p>
<p>Another GREAT advantage of this plugin is that you can use the MailChimp® signup embed forms or link forms anywhere and it will still recognize that the list is associated with your s2Member® installation. It means you can use the embed code or the link code on a totally different site, in your signature in forums, or even in your emails, and it will still communicate with s2Member® and create an account there if a new member joins through your MailChimp® forms.</p>
<p>This is a minimalistic version. There exist two commercial versions: Basic and Pro, with more features. For more info please go to http://krumch.com/kc-s2m-mc/. Also there you will find much better description of plugin features.</p>


== Installation ==

Nothing special, just a generic installation. Nothing to set - it reads what it needs from s2Members settings.

If something goes wrong (as s2Member and this plugin both are quite big and difficult plugins), just reinstall this plugin, it will re-scan both your members on the site and in the lists in your MailChimp account and will re-synchronize them again.

== Usage ==

Nothing to do, it already works for you. That's because it is "transparent"...

After installation on an old site with non-empty lists, it can need some manual work, as fully automated "initial synchronization" is not possible, task is too complex... Go to your Dashboard -> Settings -> KC S2M+MC, there will be status message.

In the admin area page you will find a list with possible features. You can vote for which one to be done first, can donate to me some bucks for faster development, and can add new features in the "wish list". As well to see a list with other plugins of mine.

== Frequently Asked Questions ==

= Why it always say "X members not exist"? =

In fact, there is no way all accounts to be synchronized, because there is lot of cases and exceptions. MailChimp® can report events slower, a member can not receive the confirmation email from MailChimp®, or not click the link yet, or member is admin in the site – all these and others can cause differences in list of members at MailChimp® and site.

= How to test that it works? =

Best way to see if the plugin works is to test manually. Create a new user in your MailChimp® list and check if it will be created in WordPress. Well, needs confirmation etc… If this do not works, click `Re-set MailChimp® site` button, wait 5-10 min (MailChimp® can be slow) and test again. Test it backward too: create new user in your site and look for it in your MailChimp® list.

== Screenshots ==

1. Admin area if s2Member® is not installed or MailChimp® API key is not set.
2. Admin area with two lists not synchronized.
3. Admin area, ideal case.

== Changelog ==

= 2016.12.12 =
* Ported to MailChimp API 3.0
* Version schema changed, to synchronise it with Basic and Pro version
* WP compability checked.

= 20150705 =
* Plugin is translated to Russian and Bulgarian (myself) and Serbian by Andrijana Nikolic <andrijanan@webhostinggeeks.com>. Other translations welcome.

= 20141023 =
* Name and URL changed to indicate that this is a free version

= 20141017 =
* Name changed to "KC a2M+MC Free"

= 20141012 =
* WP compability checked.
* License changed to GPL 2.0+
* Released as free plugin.

= 20130924 =
* Released as Basic version

= 20130518 =
* Released as the first Pro version



