<?php

/**
 * Plugin Name: HTTPS domain alias
 * Plugin URI: https://github.com/Seravo/wp-https-domain-alias
 * Description: Enable your site to have a different domains for HTTP and HTTPS. Useful e.g. if you have a wildcard SSL/TLS certificate for server but not for each site.
 * Version: 0.1
 * Author: Otto Kekäläinen / Seravo Oy
 * Author URI: http://seravo.fi
 * License: GPLv3
 */

/*  Copyright 2014  Otto Kekäläinen / Seravo Oy

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 3, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * @package HTTPS_Domain_Alias
 *
 * Swap out the current site domain with {@see HTTPS_DOMAIN_ALIAS} if the
 * protocol is HTTPS.
 *
 * This function is not bulletproof, and expects both {@see WP_SITEURL} and
 * {@see SSL_DOMAIN_ALIAS} to be defined.
 *
 * Make sure the wp-config.php defines the needed constants, e.g.
 *   define( 'WP_SITEURL', 'http://coss.fi' );
 *   define( 'SSL_DOMAIN_ALIAS', 'coss.seravo.fi' );
 *   define( 'FORCE_SSL_LOGIN', true );
 *   define( 'FORCE_SSL_ADMIN', true );
 *
 *
 * @param string $url
 * @return string
 */
function _https_domain_alias($url) {
  static $domain;
  if (!isset($domain)) {
    $domain = defined('WP_SITEURL') && defined('HTTPS_DOMAIN_ALIAS') ? parse_url( WP_SITEURL, PHP_URL_HOST) : false;
  }

  if ($domain && strpos($url, 'https') === 0 ) {
    $url = str_replace($domain, HTTPS_DOMAIN_ALIAS, $url);
  }

  return $url;
}
add_filter( 'plugins_url', '_https_domain_alias', 1 );
add_filter( 'content_url', '_https_domain_alias', 1 );
add_filter( 'site_url', '_https_domain_alias', 1 );


/**
 * Make sure no redirect never points to https://coss.fi/..
 * but instead * always to https://coss.seravo.fi/...
 *
 * Intended to be used in combination with plugin https-alias-domain.php
 *
 * @param string $url
 * @param string $status (optional, not used in this function)
 * @return string
 */
function _redirect_https_domain_rewrite($location, $status = 0) {

//debug: error_log("status=$status");
//debug: error_log("Location-i=$location");

  $locationUrl = parse_url($location);
  $siteUrl = parse_url(WP_SITEURL);
  if ($locationUrl['scheme'] == 'https' && $locationUrl['host'] == $siteUrl['host']) {
    $location = str_ireplace($siteUrl['host'], HTTPS_DOMAIN_ALIAS, $location);
  }

  //debug: error_log("Location-o=$location");
  return $location;
}
add_filter('wp_redirect', '_redirect_https_domain_rewrite');

if (!defined('WP_SITEURL')) {
  error_log("Constant WP_SITEURL is not defined");
}

if (!defined('HTTPS_DOMAIN_ALIAS')) {
  error_log("Constant HTTPS_DOMAIN_ALIAS is not defined");
}

?>
