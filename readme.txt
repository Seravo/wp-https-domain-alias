=== HTTPS domain alias ===
Contributors: otto
Tags: https, ssl, tls, alias, domain
Donate link: http://seravo.fi/
Requires at least: 3.8
Tested up to: 3.8
Stable tag: 0.1
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Enable your site to have a different domains for HTTP and HTTPS. Useful e.g. if you have a wildcard SSL/TLS certificate for server but not for each site.

== Description ==
Make sure the wp-config.php defines the needed constants, e.g.
*   define( \'WP_SITEURL\', \'http://coss.fi\' );
*   define( \'SSL_DOMAIN_ALIAS\', \'coss.seravo.fi\' );
*   define( \'FORCE_SSL_LOGIN\', true );
*   define( \'FORCE_SSL_ADMIN\', true );

== Installation ==
1. Upload \"https-domain-alias.php\" to the \"/wp-content/plugins/\" directory.
1. Activate the plugin through the \"Plugins\" menu in WordPress.

== Changelog ==
= 0.1 =
* Initial release.

== Upgrade Notice ==
= 0.1 =
* Initial release.

(This readme.txt is made to satisfy official WordPress plugin directory requirements.)

