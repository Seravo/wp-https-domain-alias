<?php
/**
 * Plugin Name: HTTPS Domain Alias
 * Plugin URI: https://github.com/Seravo/wp-https-domain-alias
 * Description: Enable your site to have a different domains for HTTP and HTTPS. Useful e.g. if you have a wildcard SSL/TLS certificate for server but not for each site.
 * Version: 1.4.3
 * Author: Seravo Oy
 * Author URI: https://seravo.com
 * License: GPLv3
 */

/** Copyright 2015-2018 Seravo Oy

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 3, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

/**
 * @package HTTPS_Domain_Alias
 *
 * Make sure a https capable domain is used {@see HTTPS_DOMAIN_ALIAS} if the
 * protocol is HTTPS.
 *
 * Make sure the wp-config.php defines the needed constants, e.g.
 *  define('FORCE_SSL_LOGIN', true);
 *  define('FORCE_SSL_ADMIN', true);
 *  define('HTTPS_DOMAIN_ALIAS', 'coss.seravo.fi');
 *
 * On a WordPress Network install HTTPS_DOMAIN_ALIAS can also be
 * defined with a wildcard, e.g. '*.seravo.fi'.
 * Compatible with WordPress MU Domain Mapping.
 *
 * Example:
 *  Redirect never points to https://coss.fi/..
 *  but instead always to https://coss.seravo.com/...
 *
 * For more information see readme.txt
 *
 * @param string $url
 * @param string $status (optional, not used in this function)
 * @return string
 */
function htsda_https_domain_rewrite( $url, $status = 0 ) {
  if ( ! is_string( $url ) ) {
    // do nothing if $url is not a string
    // the customizer view seems to sometimes pass an empty array to this filter for some reason.
    // this would crash the entire plugin
    return $url;
  }

  // Rewrite only if the request is https, or the user is logged in
  // to preserve cookie integrity
  if ( substr( $url, 0, 5 ) == 'https'
    || ( function_exists( 'is_user_logged_in' ) && is_user_logged_in()
      && ! ( defined( 'DISABLE_FRONTEND_SSL' ) && DISABLE_FRONTEND_SSL ) ) ) {

      // Assume domain is always same for all calls to this function
      // during same request and thus define some variables as static.
      static $domains;
      if ( ! isset( $domains ) ) {
        $domains = array();
        // $domain is current site URL without the www prefix
        // TODO: can we just strip www? convention says yes
        $domains[] = hstda_trim_url(parse_url( get_option( 'home' ), PHP_URL_HOST ), 'www.');
        // Also take the current request host so this works even when redirect_canonical is not in use
        $domains[] = hstda_trim_url($_SERVER['HTTP_HOST'], 'www.');
      }

      static $domainAlias;
      if ( ! isset( $domainAlias ) ) {
        $domainAlias = htsda_get_domain_alias($domain);
      }

      // If $location does not include simple https domain alias, rewrite it.
      $url = hstda_rewrite_url($url,$domains,$domainAlias);
  }
  return $url;
}

/**
 * Same as above, but handles all domains in a multisite
 */
function htsda_mu_https_domain_rewrite( $url, $status = 0 ) {
  if ( ! is_string( $url ) ) {
    // do nothing if $url is not a string
    // the customizer view seems to sometimes pass an empty array to this filter for some reason.
    // this would crash the entire plugin
    return $url;
  }

  // Rewrite only if the request is https, or the user is logged in
  // to preserve cookie integrity
  if ( substr( $url, 0, 5 ) == 'https'
    || ( function_exists( 'is_user_logged_in' ) && is_user_logged_in()
      && ! ( defined( 'DISABLE_FRONTEND_SSL' ) && DISABLE_FRONTEND_SSL ) ) ) {

      // these won't change during the request
      static $domains;

      if ( !isset( $domains ) ) {
        $args = array( 'limit' => null ); // do not limit the number of results
        $blogs = wp_get_sites( $args ); // get info from wp_blogs table
        $domains = array(); // map the domains here
        $domains[] = hstda_trim_url(parse_url( get_site_url( 1 ), PHP_URL_HOST ), 'www.'); // main site home

        // special case for wpmu domain mapping plugin
        if( function_exists('domain_mapping_siteurl') ) {
          $domains[] = hstda_trim_url(parse_url( domain_mapping_siteurl( false ), PHP_URL_HOST ), 'www.');
        }

        foreach ( $blogs as $blog ) {
          $domains[] = hstda_trim_url($blog['domain'],'www.');
        }

        // dedupe domains
        $domains = array_unique( $domains );
      }

      // Rewrite the $url
      $url = hstda_rewrite_url($url,$domains);
  }
  return $url;
}

/**
 * Gets the domain alias for the given domain
 */
function htsda_get_domain_alias( $domain ) {
  if ( substr( HTTPS_DOMAIN_ALIAS, -strlen( $domain ) ) == $domain ) {
    // Special case: $domainAlias ends with $domain,
    // which is possible in WP Network when requesting
    // the main site, don't rewrite urls as a https
    // certificate for sure exists for direct domain.
    // e.g. domain seravo.fi, domain alias *.seravo.fi
    return $domain;
  }

  else if ( substr( HTTPS_DOMAIN_ALIAS, 0, 1 ) == '*' ) {

    if(false !== strpos($domain, substr( HTTPS_DOMAIN_ALIAS, 1 )) ) {
      // never include the https domain alias part in the domain base
      $domainBase = substr( $domain, 0, strrpos( $domain, substr( HTTPS_DOMAIN_ALIAS, 1 ) ) );
    } else {
      // domain base is everything before the TLD part
      // TODO: what about dual TLD's like .co.uk ?
      $domainBase = substr( $domain, 0, strrpos( $domain, '.' ) );
    }

    // substitute dots with dashes so that we never end up in sub-sub domains
    $domainBase = str_replace('.', '-', $domainBase);
    $domainAliasBase = substr( HTTPS_DOMAIN_ALIAS, 1 );
    return $domainBase . $domainAliasBase;

  }

  else {
    return HTTPS_DOMAIN_ALIAS;
  }
}

/**
 * Debug wrapper
 *
 *
 * @param string $url
 * @param string $path
 * @param string $plugins
 * @return string $url
 */
function htsda_debug_rewrite( $url, $path=false, $plugin = false, $extra = false ) {
  error_log( "[HTTPS DOMAIN ALIAS DEBUG] in: $url" );
  $url = is_multisite() ? htsda_mu_https_domain_rewrite( $url ) : htsda_https_domain_rewrite( $url );
  error_log( "[HTTPS DOMAIN ALIAS DEBUG] out: $url" );
  return $url;
}

/**
 * Rewrites specific for homeurl
 */
function htsda_home_url_rewrite( $url ) {
  // Store the original url in a global constant so that we can use it later to fix things
  if ( !defined('HTTPS_DOMAIN_ALIAS_FRONTEND_URL') ) {
    $parsed_url = parse_url( $url );
    define( 'HTTPS_DOMAIN_ALIAS_FRONTEND_URL', $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path']);
  }

  // don't rewrite urls for polylang settings page
  if ( isset($_GET['page']) && $_GET['page'] == 'mlang' ) {
    return $url;
  }

  $url = is_multisite() ? htsda_mu_https_domain_rewrite( $url ) : htsda_https_domain_rewrite( $url );
  return $url;
}

/**
 * Additional functionality to make all links relative
 *
 * This helps keep the front end clean of any HTTPS domain alias links
 *
 * Thanks to @chernjie for the idea!
 */

/**
 * This converts any link to slash-relative
 *
 * NOTE: this applies to external links as well, so be careful with this!
 */
function htsda_root_relative_url( $url, $html ) {
  // links may be scheme agnostic (starting with //)
  // in this case we want to temporarily add a scheme for parse_url to work
  if ( substr( $url, 0, 2 ) === "//" ) {
    $url = 'http:' . $url; // -> is now a full-form url.
    // scheme doesn't matter since it's removed anyways during the next step
  }

  // If url is already relative, do nothing
  if ( substr( $url, 0, 4 ) != "http" ) return $html;

  $p = parse_url( $url );
  $root = $p['scheme'] . "://" . $p['host'];
  $html = str_ireplace( $root, '', $html );

  return $html;
}

/*
 * Media gallery images should be handled with relative urls
 */
function htsda_root_relative_image_urls( $html, $id, $caption, $title, $align, $url, $size, $alt ) {
  return htsda_root_relative_url( $url, $html );
}
function htsda_root_relative_media_urls( $html, $id, $att ) {
  return htsda_root_relative_url( $att['url'], $html );
}

/*
 * This adds a small javascript fix for the TinyMCE link adder dialog
 */
function htsda_link_adder_fix( $Hook ) {
  if ( 'post.php' === $Hook || 'post-new.php' === $Hook ) {
    // we only need to use this fix in post.php
    wp_enqueue_script( 'link-relative', plugin_dir_url( __FILE__ ) . 'link-relative.js' );
  }
}

/*
 * Register filters only if HTTPS_DOMAIN_ALIAS defined
 */
if ( defined( 'HTTPS_DOMAIN_ALIAS' ) ) {
  // A redirect or link to https may happen from pages served via http
  $domain_filter = is_multisite() ? 'htsda_mu_https_domain_rewrite' : 'htsda_https_domain_rewrite';

  add_filter( 'login_url',                   $domain_filter, 20 );
  add_filter( 'logout_url',                  $domain_filter, 20 );
  add_filter( 'admin_url',                   $domain_filter, 20 );
  add_filter( 'wp_redirect',                 $domain_filter, 20 );
  add_filter( 'plugins_url',                 $domain_filter, 20 );
  add_filter( 'content_url',                 $domain_filter, 20 );
  add_filter( 'theme_mod_header_image',      $domain_filter, 20 );
  add_filter( 'wp_get_attachment_url',       $domain_filter, 20 );
  add_filter( 'wp_get_attachment_thumb_url', $domain_filter, 20 );
  add_filter( 'site_url',                    $domain_filter, 20 );
  add_filter( 'home_url',                    'htsda_home_url_rewrite', 20 );

  // Force relative urls for links created in the wp-admin
  add_filter( 'media_send_to_editor', 'htsda_root_relative_media_urls', 10, 3 );
  add_filter( 'image_send_to_editor', 'htsda_root_relative_image_urls', 10, 9 );
  add_action( 'admin_enqueue_scripts', 'htsda_link_adder_fix' );

  // For Polylang compatibility, we need to tell it not to cache home urls for the languages
  // Thanks @sippis, for helping us find this bug !
  if( ! defined( 'PLL_CACHE_HOME_URL' ) ) {
    define( 'PLL_CACHE_HOME_URL', false );
  }
  else if( PLL_CACHE_HOME_URL ) {
    // TODO: if set to true, we should show an incompatibility warning
  }

} else {
  error_log( 'Constant HTTPS_DOMAIN_ALIAS is not defined' );
}

/*
 * Make sure this plugin loads as first so that the filters will be applied
 * to all plugins before they fetch or define any URLs.
 *
 * This function will only take effect at the time when some plugin is activated,
 * does not apply directly for old installs and in general is brittle to brake,
 * as something else might edit the plugin list in the database options table
 * and thus lower the priorty for this plugin.
 */
add_action( 'activated_plugin', 'htsda_https_domain_alias_must_be_first_plugin' );
function htsda_https_domain_alias_must_be_first_plugin() {
  // ensure path to this file is via main wp plugin path
  $wp_path_to_this_file = preg_replace( '/(.*)plugins\/(.*)$/', WP_PLUGIN_DIR."/$2", __FILE__ );
  $this_plugin = plugin_basename( trim( $wp_path_to_this_file ) );
  $active_plugins = get_option( 'active_plugins' );
  $this_plugin_key = array_search( $this_plugin, $active_plugins );

  if ( $this_plugin_key ) { // if it's 0 it's the first plugin already, no need to continue
    array_splice( $active_plugins, $this_plugin_key, 1 );
    array_unshift( $active_plugins, $this_plugin );
    update_option( 'active_plugins', $active_plugins );
  }
}

/*
 * A function for determining if we're currently on a login page
 */
function is_login_page() {
  return in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) );
}

/*
 * Redirects non logged in visitors away from the visible domain alias front
 * end. This also makes sure search engines don't index the domain alias but
 * rather the actual canonical site thus improving site SEO.
 */
add_action( 'wp', 'htsda_https_domain_alias_redirect_visitors' );
function htsda_https_domain_alias_redirect_visitors() {
  // check if visitor is currently in a domain alias location
  $is_on_domain_alias = strpos( get_option( 'HOME' ), $_SERVER['HTTP_HOST'] );

  if (  ! $is_on_domain_alias &&
      ! is_user_logged_in() &&
      ! is_login_page() )  {
    wp_redirect(get_option( 'HOME' ) . $_SERVER['REQUEST_URI'], 301 );
  }
}

/**
 * Show a readme page in the settings menu if HTTPS_DOMAIN_ALIAS is not defined
 */
add_action( 'admin_menu', 'htsda_https_domain_alias_readme' );
function htsda_https_domain_alias_readme() {
  // readme is only visible, when HTTPS_DOMAIN_ALIAS is not defined
  if ( ! defined( 'HTTPS_DOMAIN_ALIAS' ) ) {
    add_options_page( 'HTTPS Domain Alias', 'HTTPS Domain Alias', 'administrator', __FILE__, 'htsda_build_readme_page', plugins_url( '/images/icon.png', __FILE__ ) );
  }
}

/*
 * Helper: Rewrite url if it's pointing to this site
 *
 * @param string    well formed url
 * @param array    domains of the site
 * (optional)@param string    ssl secured domain alias of the site
 */
function hstda_rewrite_url($url ,$domains , $domainAlias = NULL ) {
  // $url must start with http
  // => don't touch relative or non http/https urls
  if( substr( $url, 0, 4) != 'http' ) {
    return $url;
  }

  $parts = parse_url($url);

  // Strip www. from url
  $parts['host'] = hstda_trim_url( $parts['host'], 'www.' );

  // Only rewrite local urls
  if ( isset( $parts['host'] ) && !in_array( $parts['host'], $domains ) ) {
    return $url; // If the host is eg. twitter.com leave it unchanged
  } else {
    $parts['scheme'] = 'https';
    $parts['host'] = isset($domainAlias) ? $domainAlias : htsda_get_domain_alias( $parts['host'] );
    // TODO Is there cases where we should also replace $parts['query'] ?
    return hstda_build_url($parts);
  }
}

/*
 * Helper: Build an URL
 * Useful for building new url from @return value of parse_url()
 *
 * @param mixed     (Part(s) of) an URL in form of a string or associative array like parse_url() returns
 * @param mixed     Same as the first argument
 */
function hstda_build_url( $parts ) {
  return
     ((isset($parts['scheme'])) ? $parts['scheme'] . '://' : '')
    .((isset($parts['user'])) ? $parts['user'] . ((isset($parts['pass'])) ? ':' . $parts['pass'] : '') .'@' : '')
    .((isset($parts['host'])) ? $parts['host'] : '')
    .((isset($parts['port'])) ? ':' . $parts['port'] : '')
    .((isset($parts['path'])) ? $parts['path'] : '')
    .((isset($parts['query'])) ? '?' . $parts['query'] : '')
    .((isset($parts['fragment'])) ? '#' . $parts['fragment'] : '');
}

/*
 * Trims $prefix if it exists in the beginning of the string
 * This is alot faster than regex
 * See: http://stackoverflow.com/a/4517270/1337062
 */
function hstda_trim_url( $str, $prefix ) {
  if ( substr( $str, 0, strlen( $prefix ) ) === $prefix ) {
    $str = substr( $str, strlen( $prefix ) );
  }
  return $str;
}

/*
 * Display the readme page
 */
function htsda_build_readme_page() {
?>
<div class="wrap">
  <h2>HTTPS Domain Alias</h2>
  <div id="message" class="error">
    <p><?php _e('This readme page is only visible when HTTPS_DOMAIN_ALIAS is not defined in wp-config.php. You will not see this once the constant is defined.', 'htsda' );?></p>
  </div>
  <?php include( 'admin-readme.html' ); ?>
  <p>&nbsp;</p>
  <p><small>HTTPS Domain Alias is made by <a href="https://seravo.com/">Seravo Oy</a>, which specialize
    in open source support services and among others is the only company in Finland to provide
    [WordPress Premium Hosting](https://seravo.com/wordpress-palvelu).</small></p>
</div>
<?php
}

/**
 * A helper sorter function by string length
 */
function _by_length($a, $b){
  return strlen($b) - strlen($a);
}

/*
 * Change how the permalink is shown in backend editor view
 */
add_filter( 'get_sample_permalink_html', 'htsda_sample_permalink_html', 5, 1);
function htsda_sample_permalink_html( $content ){
  if ( defined( 'HTTPS_DOMAIN_ALIAS_FRONTEND_URL' ) ) {
    $domain_alias = htsda_home_url_rewrite( HTTPS_DOMAIN_ALIAS_FRONTEND_URL );

    // Replace url between <a> tags
    // If we just replace from everywhere it breaks preview links
    $content = str_replace( '>' . $domain_alias, '>' . HTTPS_DOMAIN_ALIAS_FRONTEND_URL, $content);
  }
  return $content;
}
