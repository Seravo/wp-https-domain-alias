=== HTTPS Domain Alias ===
Contributors: ottok, zuige, onnimonni
Tags: https, ssl, tls, alias, domain, seravo
Donate link: https://seravo.com/
Requires at least: 3.7
Tested up to: 4.9.4
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Enable your site to have a different domains for HTTP and HTTPS.

== Description ==

This plugin is useful e.g. if you have a wildcard SSL/TLS certificate for server but not for each site.

If the site is normally at say `http://example.org/` and you want to have the admin area https protected, but you don't have a SSL/TLS certificate so that `https://example.org/` would work, you can define another domain for secure connections.

For example instead of `https://example.org/wp-login.php` or `https://example.org/wp-admin/` the user is redirected to `https://example.seravo.com/wp-login.php` or `https://example.seravo.com/wp-admin/`.

This plugin works with both normal WordPress installations and WordPress Network installation and is compatible with the WordPress MU Domain Mapping plugin.

The code is optimized to be fast and does not for example do any database lookups or use cookies.

This plugin is made by [Seravo Oy](https://seravo.com/), which specializes in open source support services and among others is the only company in Finland to provide [WordPress Premium Hosting](https://seravo.com/).

Source available at https://github.com/Seravo/wp-https-domain-alias

== Installation ==

1. Upload plugin to the `/wp-content/plugins/` directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Make sure the `wp-config.php` defines the needed constants.

Example:

    define('FORCE_SSL_ADMIN', true);
    define('HTTPS_DOMAIN_ALIAS', 'example.org');

The plugin scenario assumes the site domain is example.com but there is no https certificate for it. Instead there is a https certificate for example.org, which has been defined as the HTTPS_DOMAIN_ALIAS.

In a WordPress Network installation the HTTPS_DOMAIN_ALIAS can be defined as *.example.org and then <domain.tld> will be redirected to <domain>.example.org. This plugin is designed to be compatible with
the WordPress MU Domain Mapping plugin.

Possible values of $location when calling this function

 - http://example.com
 - https://example.com         <- the case where https fails and we want to avoid
 - http://example.example.org
 - https://example.example.org <- the case where https works

== Frequently Asked Questions ==

= Does this work for WordPress Network? =

Yes, since version 0.4.

= Where is the UI? =

This plugin has no visible UI, the magic happens automatically if the plugin is active.

= What does FORCE_SSL_ADMIN do? =

See http://codex.wordpress.org/Administration_Over_SSL

Note that defining FORCE_SSL_LOGIN is not needed.

== Screenshots ==

Example when on **coss.fi** HTTPS_DOMAIN_ALIAS is **coss.seravo.fi**:

    $ curl -I http://coss.fi/wp-admin/
    HTTP/1.1 302 Found
    Location: https://coss.seravo.com/wp-admin/

== Changelog ==

Note that complete commit log is available at https://github.com/Seravo/wp-https-domain-alias/commits/master
= 1.4.2 =
Bugfix: Only convert non-relative links to their root relative form

= 1.4.1 =
Don't try to rewrite relative urls

= 1.4 =
* Fix Polylang compatibility
* The edit.php permalink now displays the real domain, instead of alias

= 1.3.2 =
Added tests

= 1.3 =
* Wildcard domain aliases are now handled differently
  http://www.nonsecure.com -> https://nonsecure.secure.com
  http://site.nonsecure.com -> https://site-nonsecure.secure.com
  as opposed to previous behaviours
  http://www.nonsecure.com -> https://www.secure.com
  http://site.nonsecure.com -> https://site.secure.com
* Minor refactoring to the code
* Bugfixes

= 1.2.1 =
* Fix missing script from source files

= 1.2 =
* Includes automatic relative URLS in the front end

= 1.1 =
* Adds rewrite capabilities for all domains in multisite

= 1.0 =
* Mature enough for official 1.0 release

= 0.9 =
* All OK!

= 0.8 =
* Fix home_url infinite loop and thus enable rewrites for it too

= 0.7 =
* Added debug wrapper and made sure this plugin is load first of all plugins.

= 0.6 =
* Bugfixes for preview mode and non-admin https pages.

= 0.5 =
* Updated readme.txt

= 0.4 =
* Enhanced to also support WordPress Network installations.
* Refactored code to be robust in all known situations.

= 0.3 =
* Merged pull request on http preview

= 0.2 =
* Improved readme.txt. Log error if the needed constants don't exist.

= 0.1 =
* Initial release.

== Upgrade Notice ==
