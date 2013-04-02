=== Automatic Domain Changer ===
Contributors: nuagelab
Tags: admin, domain change
Requires at least: 3.0
Tested up to: 3.5.1
Stable tag: trunk
License: GPLv2 or later

Automatically detects a domain name change, and updates all the WordPress tables in the database to reflect this change.

== Description ==

This plugin automatically detects a domain name change, and updates all the WordPress tables in the database to reflect this change.

= Features =

* Easily migrate a WordPress site from one domain to another

Note: NuageLab collects usage information about this plugin so that we can better serve our customers and know what features to add. By installing and activating this plugin, you agree to these terms.

== Installation ==

This section describes how to install the plugin and get it working.

= Installing the Plugin =

*(using the Wordpress Admin Console)*

1. From your dashboard, click on "Plugins" in the left sidebar
1. Add a new plugin
1. Search for "Automatic Domain Changer"
1. Install "Automatic Domain Changer"
1. Once Installed, if you want to manually change your domain, go to Tools > Domain Change
1. If your domain changes, a notice will appear at the top of the admin screen with a link to the domain changing tool

*(manually via FTP)*

1. Delete any existing 'auto-domain-change' folder from the '/wp-content/plugins/' directory
1. Upload the 'auto-domain-change' folder to the '/wp-content/plugins/' directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Once Installed, if you want to manually change your domain, go to Tools > Domain Change
1. If your domain changes, a notice will appear at the top of the admin screen with a link to the domain changing tool

== Frequently Asked Questions ==

= What does this plugin do precisely? =

It scans all the tables with the same table prefix as WordPress. It fetches each row, unserialize values as needed, and replace the old domain by the new.

= Do you plan to localize this plugin in a near future? =

Yes, this plugin will be translated to french shortly. If you want to help with translation in other languages, we'll be happy to hear from you.

== Screenshots ==

1. The domain change and admin notice

== Changelog ==
= 0.0.1 =
* First released version. Tested internally with about 10 sites.

== Upgrade Notice ==