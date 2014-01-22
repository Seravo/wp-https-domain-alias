=== HTTPS domain alias ===
Contributors: ottok
Tags: https, ssl, tls, alias, domain
Donate link: http://seravo.fi/
Requires at least: 3.8
Tested up to: 3.8
Stable tag: 0.4
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Enable your site to have a different domains for HTTP and HTTPS.

== Description ==

This plugin is useful e.g. if you have a wildcard SSL/TLS certificate for server but not for each site.

If the site is normally at say `http://example.org/` and you want to have the admin area https protected, but you don't have a SSL/TLS certificate so that `https://example.org/` would work, you can define another domain for secure connections.

For example instead of `https://example.org/wp-login.php` or `https://example.org/wp-admin/` the user is redirected to `https://example.seravo.fi/wp-login.php` or `https://example.seravo.fi/wp-admin/`.

Example when on coss.fi HTTPS_DOMAIN_ALIAS is 'coss.seravo.fi':

        $ curl -I http://coss.fi/wp-admin/
        HTTP/1.1 302 Found
        Location: https://coss.seravo.fi/wp-admin/

This plugin also works with both normal WordPress installations and WordPress Network installation and compatible with WordPress MU Domain Mapping. The code is optimized to be fast and does not for example do any database lookups or use cookies.

This plugin is made by [Seravo Oy](http://seravo.fi/), which specializes in open source support services and among others is the only company in Finland to provide (WordPress Premium Hosting)[http://seravo.fi/wordpress-palvelu].

Source available at https://github.com/Seravo/wp-https-domain-alias

== Installation ==
1. Upload `https-domain-alias.php` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Make sure the `wp-config.php` defines the needed constants, e.g.:
        define('FORCE_SSL_LOGIN', true);
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

Probably not. This plugin is intended for WP single installations. For WP Network installs there are other ways to achieve the same result.

= Where is the UI? =

This plugin has no visible UI, the magic happens automatically is the plugin is active.

== Screenshots ==

None.

== Changelog ==

= 0.4 =
* Enhanced to also support WordPress Network installations.
* Refactored code to be robust in all known situations.

= 0.3 =
* Merged pull request on http preview

= 0.2 =
* Improved readme.txt. Log error if the needed constats don't exist.

= 0.1 =
* Initial release.

== Upgrade Notice ==

= 0.1 =
* Initial release.

(This readme.txt is made to satisfy official WordPress plugin directory requirements.)

