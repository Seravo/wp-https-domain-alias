=== HTTPS domain alias ===
Contributors: ottok
Tags: https, ssl, tls, alias, domain
Donate link: http://seravo.fi/
Requires at least: 3.8
Tested up to: 3.8
Stable tag: 0.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Enable your site to have a different domains for HTTP and HTTPS.

== Description ==

This plugin is useful e.g. if you have a wildcard SSL/TLS certificate for server but not for each site.

If the site is normally at say `http://example.org/` and you want to have the admin area https protected, but you don't have a SSL/TLS certificate so that `https://example.org/` would work, you can define another domain for secure connections to that instead of `https://example.org/wp-login.php` or `https://example.org/wp-admin/` the user is redirected to `https://example.example.com/wp-login.php` and `https://example.example.com/wp-admin/`.

This plugin is made by [Seravo Oy](http://seravo.fi/), the Finnish WordPress and open source experts company.

Source available at https://github.com/Seravo/wp-https-domain-alias

== Installation ==
1. Upload `https-domain-alias.php` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Make sure the `wp-config.php` defines the needed constants, e.g.:
        define( 'WP_SITEURL', 'http://example.org' );
        define( 'HTTPS_DOMAIN_ALIAS', 'example.example.com' );
        define( 'FORCE_SSL_LOGIN', true );
        define( 'FORCE_SSL_ADMIN', true );

== Frequently Asked Questions ==

= Does this work for WordPress Network? =

Probably not. This plugin is intended for WP single installations. For WP Network installs there are other ways to achieve the same result.

= Where is the UI? =

This plugin has no visible UI, the magic happens automatically is the plugin is active.

== Screenshots ==

None.

== Changelog ==

= 0.2 =
* Improved readme.txt. Log error if the needed constats don't exist.

= 0.1 =
* Initial release.

== Upgrade Notice ==

= 0.1 =
* Initial release.

(This readme.txt is made to satisfy official WordPress plugin directory requirements.)

