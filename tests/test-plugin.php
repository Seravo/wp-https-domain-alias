<?php

class PluginTest extends WP_UnitTestCase {

  // Check that that activation doesn't break
  function test_plugin_activated() {
    $this->assertTrue( is_plugin_active( PLUGIN_PATH ) );
  }

  function test_shouldnt_change_url() {
    $url = 'https://www.twitter.com/intent/tweet?via=Test&text=Test&url=http%3A%2F%2Fexample.com%2Ftest%2Fexample%2F';
    $domains = ['example.com','example.fi'];
    $this->assertEquals( $url,hstda_rewrite_url($url,$domains) );
  }

  function test_should_change_url() {
    $url = 'http://www.example.com/example/path';
    $domainAlias = "example.seravo.fi";
    $should_url = 'https://'.$domainAlias.'/example/path';
    $domains = ['example.fi','example.com'];
    $this->assertEquals( $should_url,hstda_rewrite_url($url,$domains,$domainAlias) );
  }

  function test_with_alias_defined_should_change_url() {
    define('HTTPS_DOMAIN_ALIAS','example.seravo.fi');
    $url = 'http://www.example.com/example/path';
    $should_url = 'https://'.HTTPS_DOMAIN_ALIAS.'/example/path';
    $domains = ['example.com'];
    $this->assertEquals( $should_url,hstda_rewrite_url($url,$domains) );
  }
}

