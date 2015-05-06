<?php
/**
 * Plugin Name: HTTPS domain alias
 * Plugin URI: https://github.com/Seravo/wp-https-domain-alias
 * Description: Enable your site to have a different domains for HTTP and HTTPS. Useful e.g. if you have a wildcard SSL/TLS certificate for server but not for each site.
 * Version: 1.3.1
 * Author: Seravo Oy
 * Author URI: http://seravo.fi
 * License: GPLv3
 */

/** Copyright 2015 Seravo Oy

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
 *  but instead always to https://coss.seravo.fi/...
 *
 * For more information see readme.txt
 *
 * @param string $url
 * @param string $status (optional, not used in this function)
 * @return string
 */
function htsda_https_domain_rewrite( $url, $status = 0 ) {

  // Rewrite only if the request is https, or the user is logged in
  // to preserve cookie integrity
  if ( substr( $url, 0, 5 ) == 'https'
    || ( function_exists( 'is_user_logged_in' ) && is_user_logged_in()
      && ! ( defined( 'DISABLE_FRONTEND_SSL' ) && DISABLE_FRONTEND_SSL ) ) ) {

      // Assume domain is always same for all calls to this function
      // during same request and thus define some variables as static.
      static $domain;
      if ( ! isset( $domain ) ) {
        // $domain is current site URL without the www prefix
        // TODO: can we just strip www? convention says yes
        $domain = preg_replace('/^www\./i', '', parse_url( get_option( 'home' ), PHP_URL_HOST ));
      }

      static $domainAlias;
      if ( ! isset( $domainAlias ) ) {
        $domainAlias = htsda_get_domain_alias($domain);
      }

      // If $location does not include simple https domain alias, rewrite it.
      if ( $domain != $domainAlias ) {
        $url = str_ireplace( $domain, $domainAlias, $url );
        $url = str_ireplace( '//www.', '//', $url); //strip www to avoid sub-sub-domain structure
        $url = str_replace( 'http://', 'https://', $url );
      }
  }
  return $url;
}


/**
 * Same as above, but handles all domains in a multisite
 */
function htsda_mu_https_domain_rewrite( $url, $status = 0 ) {

  // Rewrite only if the request is https, or the user is logged in
  // to preserve cookie integrity
  if ( substr( $url, 0, 5 ) == 'https'
    || ( function_exists( 'is_user_logged_in' ) && is_user_logged_in()
      && ! ( defined( 'DISABLE_FRONTEND_SSL' ) && DISABLE_FRONTEND_SSL ) ) ) {

      // these won't change during the request  
      static $domains;

      if ( !isset( $domains ) ) {
        $blogs = wp_get_sites(); // get info from wp_blogs table
        $domains = array(); // map the domains here
        $domains[] = preg_replace('/^www\./i', '', parse_url( get_site_url( 1 ), PHP_URL_HOST )); // main site home

        // special case for wpmu domain mapping plugin
        if( function_exists('domain_mapping_siteurl') ) {
          $domains[] = preg_replace('/^www\./i', '', parse_url( domain_mapping_siteurl( false ), PHP_URL_HOST ));
        } 

        foreach ( $blogs as $blog ) {
          $domains[] = preg_replace('/^www\./i', '', $blog['domain']);
        }

        // dedupe domains
        $domains = array_unique( $domains );

		// order by string length so that we prefer the longest possible match
        usort($domains, '_by_length');

      }

      foreach ($domains as $domain) {
        if ( strpos( $url, $domain ) ) {
          // url is part of the network!

          // get corresponding domain alias for this domain
          $domainAlias = htsda_get_domain_alias($domain);

          // If $location does not include simple https domain alias, rewrite it.
          if ( $domain != $domainAlias ) {
            $url = str_ireplace( $domain, $domainAlias, $url );
            $url = str_ireplace( '//www.', '//', $url); //strip www to avoid sub-sub-domain structure
            $url = str_replace( 'http://', 'https://', $url );
          }

          // rewrite done, no need to keep looping
          break;
        }
      }
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
  error_log( "in: $url" );
  $url = is_multisite() ? htsda_mu_https_domain_rewrite( $url ) : htsda_https_domain_rewrite( $url );
  error_log( "out: $url" );
  return $url;
}

/**
 * Includes a patch for Polylang Language plugin, which redefines home_url in the back-end
 */
function htsda_home_url_rewrite( $url ) {

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
  // If urls already start from root, just return it
  if ( $url[0] == "/" ) return $html;

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
function htsda_link_adder_fix( $hook ) {
  if ( 'post.php' === $hook || 'post-new.php' === $hook ) {
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

  add_filter( 'login_url',                   $domain_filter );
  add_filter( 'logout_url',                  $domain_filter );
  add_filter( 'admin_url',                   $domain_filter );
  add_filter( 'wp_redirect',                 $domain_filter );
  add_filter( 'plugins_url',                 $domain_filter );
  add_filter( 'content_url',                 $domain_filter );
  add_filter( 'theme_mod_header_image',      $domain_filter );
  add_filter( 'wp_get_attachment_url',       $domain_filter );
  add_filter( 'wp_get_attachment_thumb_url', $domain_filter );
  add_filter( 'site_url',                    $domain_filter );
  add_filter( 'home_url',                    'htsda_home_url_rewrite' );

  // Force relative urls for links created in the wp-admin 
  add_filter( 'media_send_to_editor', 'htsda_root_relative_media_urls', 10, 3 );
  add_filter( 'image_send_to_editor', 'htsda_root_relative_image_urls', 10, 9 );
  add_action( 'admin_enqueue_scripts', 'htsda_link_adder_fix' ); 

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
 * Create a readme page in the settings menu
 */
add_action( 'admin_menu', 'htsda_https_domain_alias_readme' );
function htsda_https_domain_alias_readme() {
  //Readme is only visible, when HTTPS_DOMAIN_ALIAS is not defined
  if ( ! defined( 'HTTPS_DOMAIN_ALIAS' ) ) {
    add_options_page( 'HTTPS Domain Alias', 'HTTPS Domain Alias', 'administrator', __FILE__, 'htsda_build_readme_page', plugins_url( '/images/icon.png', __FILE__ ) );
  }
}

/*
 * Display the readme page
 */
function htsda_build_readme_page() { ?>
  <div class="wrap">
    <h2>HTTPS Domain Alias</h2>
    <div id="message" class="error">
      <p><?php _e('This readme page is only visible when HTTPS_DOMAIN_ALIAS is not defined in wp-config.php. You will not see this once the constant is defined.', 'htsda' );?></p>
    </div>
    <?php include( 'admin-readme.html' ); ?>
    <p>&nbsp;</p>
    <p><small>HTTPS Domain Alias is made by <a href="http://seravo.fi/">Seravo Oy</a>, which specialize
      in open source support services and among others is the only company in Finland to provide
      [WordPress Premium Hosting](http://seravo.fi/wordpress-palvelu).</small></p>
  </div>
  <?php
}

/**
 * A helper sorter function by string length
 */
function _by_length($a, $b){
  return strlen($b) - strlen($a);
}

/**
 * Strip off all site_url hostnames
 * Slightly different from wp_make_link_relative
 * @param $output
 * @return string
 */
function strip_absolute_url($output) {
	static $samples;
	if (empty($samples))
	{
		$site_url = site_url();
		remove_filter('site_url', 'htsda_https_domain_rewrite');
		$original_url = site_url();
		add_filter('site_url', 'htsda_https_domain_rewrite');
		$samples = array_unique(array(
			str_replace('http://', 'https://', $original_url),
			str_replace('https://', 'http://', $original_url),
			str_replace('http://', 'https://', site_url()),
			str_replace('https://', 'http://', site_url()),
		));
	}
	foreach ($samples as $sample)
		$output = str_replace($sample, '', $output);
	return $output;
}
if ( defined( 'HTTPS_DOMAIN_ALIAS' ) ) {
	add_filter( 'content_save_pre',    'strip_absolute_url' );
	add_filter( 'excerpt_save_pre',    'strip_absolute_url' );
	add_filter( 'comment_save_pre',    'strip_absolute_url' );
	add_filter( 'pre_comment_content', 'strip_absolute_url' );
}