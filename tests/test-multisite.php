<?php

class MultisiteTest extends WP_UnitTestCase {

  // Check that that activation doesn't break
  function test_plugin_activated() {
    $this->assertTrue( is_plugin_active( PLUGIN_PATH ) );
  }

  function test_with_alias_defined_multisite_should_change_url() {
    define('HTTPS_DOMAIN_ALIAS','*.seravo.fi');
    $url = 'http://www.example.com/example/path';
    $should_url = 'https://'.htsda_get_domain_alias('example.com').'/example/path';
    $domains = ['example.com'];
    $this->assertEquals( $should_url,hstda_rewrite_url($url,$domains) );
  }
}

