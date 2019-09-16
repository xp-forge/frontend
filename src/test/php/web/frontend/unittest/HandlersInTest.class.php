<?php namespace web\frontend\unittest;

use lang\reflect\Package;
use unittest\TestCase;
use web\frontend\HandlersIn;

class HandlersInTest extends TestCase {

  #[@test]
  public function can_create_with_string() {
    new HandlersIn('web.frontend.unittest.actions');
  }

  #[@test]
  public function can_create_with_package() {
    new HandlersIn(Package::forName('web.frontend.unittest.actions'));
  }

  #[@test]
  public function patterns_sorted_by_length() {
    $delegates= new HandlersIn('web.frontend.unittest.actions');
    $this->assertEquals(
      [
        '#get/blogs/(?<category>[^/]+)/(?<id>[0-9]+)$#',
        '#get/users/(?<id>[^/]+)/avatar$#',
        '#get/users/(?<id>[^/]+)$#',
        '#post/users$#',
        '#get/users$#',
        '#get/?$#'
      ],
      array_keys($delegates->patterns)
    );
  }
}