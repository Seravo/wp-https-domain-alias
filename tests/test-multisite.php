<?php

class MultisiteTest extends WP_UnitTestCase {


  // We need to forget earlier defined HTTPS_DOMAIN_ALIAS
  public function run(PHPUnit_Framework_Test $result = NULL) {
      $this->setPreserveGlobalState(false);
      return parent::run($result);
  }

  /**
   * @runInSeparateProcess
   * @preserveGlobalState disabled
   */
  function test_with_alias_defined_for_multisite_should_rewrite_url() {
    define('HTTPS_DOMAIN_ALIAS','*.seravo.fi');
    $url = 'http://www.example.com/example/path';
    $should_url = 'https://'.htsda_get_domain_alias('example.com').'/example/path';
    $domains = ['example.com'];
    $this->assertEquals( $should_url,hstda_rewrite_url($url,$domains) );
  }
}

