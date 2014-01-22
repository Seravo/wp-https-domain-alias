<?php

/**
 * Plugin Name: HTTPS domain alias
 * Plugin URI: https://github.com/Seravo/wp-https-domain-alias
 * Description: Enable your site to have a different domains for HTTP and HTTPS. Useful e.g. if you have a wildcard SSL/TLS certificate for server but not for each site.
 * Version: 0.3
 * Author: Otto Kekäläinen / Seravo Oy
 * Author URI: http://seravo.fi
 * License: GPLv3
 */

/*
The plugin scenario assumes the site domain is example.com but there is no
https certificate for it. Instead there is a https certificate for
example.org, which has been defined as the HTTPS_DOMAIN_ALIAS.

In a WordPress Network installation the HTTPS_DOMAIN_ALIAS can be defined
as *.example.org and then <domain.tld> will be redirected
to <domain>.example.org. This plugin is designed to be compatible with
the WordPress MU Domain Mapping plugin.

Possible values of $location when calling this function
 - http://example.com
 - https://example.com         <- the case where https fails and we want to avoid
 - http://example.example.org
 - https://example.example.org <- the case where https works

* Function logic:
*  1. If scheme http, don't rewrite.
*  2. If scheme https and (*.)example.org, don't rewrite.
*  3. If scheme https and domain not (*.)example.org, rewrite top-level
*     domain with example.org. E.g. crecco.fi -> crecco.seravo.fi.
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
 * This function is not bulletproof, and requires {@see HTTPS_DOMAIN_ALIAS}
 * to be defined.
 *
 * Make sure the wp-config.php defines the needed constants, e.g.
 *   define('FORCE_SSL_LOGIN', true);
 *   define('FORCE_SSL_ADMIN', true);
 *   define('HTTPS_DOMAIN_ALIAS', 'coss.seravo.fi');
 *
 * On a WordPress Network install HTTPS_DOMAIN_ALIAS can also be
 * defined with a wildcard, e.g. '*.seravo.fi'.
 *
 * The function site_url() will always return the URL, e.g. 'http://coss.fi'
 *
 * @param string $url
 * @return string
 */
function _https_domain_alias($url) {
  static $domain;
  //debug: error_log("url i=$url");

  if (!isset($domain)) {
    if (home_url() && defined('HTTPS_DOMAIN_ALIAS')) {
      $domain = parse_url(home_url(), PHP_URL_HOST);
    } else {
      $domain = false;
    }
  }

  if ($domain && strpos($url, 'https') === 0 ) {
    $url = str_replace($domain, HTTPS_DOMAIN_ALIAS, $url);
  }

  //debug: error_log("url o=$url");
  return $url;
}


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
  //debug:
  error_log("Location-i=$location");

  // Parse redirect URL from WordPress
  $locationUrl = parse_url($location);

  // If scheme http, don't rewrite.
  if ($locationUrl['scheme'] == 'https') {

    // Check $location differently if wildcard defined (likely to be a
    // WordPress Network) or not (likely a single WordPress installation).
    if (strpos(HTTPS_DOMAIN_ALIAS, '*') !== false) {

      $wildcardDomainAlias = substr(HTTPS_DOMAIN_ALIAS, 2);
      //debug: error_log("wildcardDomainAlias=$wildcardDomainAlias");

      // If $location does not include wildcard domain alias, rewrite it.
      if (strpos($locationUrl['host'], $wildcardDomainAlias) === false) {
        $locationDomainBase = substr($locationUrl['host'], 0, strrpos($locationUrl['host'], '.'));
        //debug: error_log("locationDomainBase=$locationDomainBase");
        $location = str_ireplace($locationUrl['host'], "$locationDomainBase.$wildcardDomainAlias", $location);
      }

    } else {

      // If $location does not include simple https domain alias, rewrite it.
      if ($locationUrl['host'] != HTTPS_DOMAIN_ALIAS) {
        $location = str_ireplace($locationUrl['host'], HTTPS_DOMAIN_ALIAS, $location);
      }

    }

  }

  //debug:
  error_log("Location-o=$location");
  return $location;
}


/**
 * Use HTTP URL to preview posts
 *
 * Preview posts should not be done via HTTPS to avoid mixed-content error
 *
 * @param null $null (no parameter is passed)
 * @return string $url
 */
function _set_preview_link() {
  error_log('here2');

    $http_home_url = home_url();
    $slug = basename( get_permalink() );
    return $http_home_url . $slug . '&preview=true';
}


/*
 * Register filters only if HTTPS_DOMAIN_DEFINED
 */
if (defined('HTTPS_DOMAIN_ALIAS')) {
  add_filter('plugins_url', '_https_domain_alias', 1);
  add_filter('content_url', '_https_domain_alias', 1);
  add_filter('site_url', '_https_domain_alias', 1);

  add_filter('preview_post_link', '_set_preview_link');

  add_filter('wp_redirect', '_redirect_https_domain_rewrite');
} else {
  error_log("Constant HTTPS_DOMAIN_ALIAS is not defined");
}

?>
