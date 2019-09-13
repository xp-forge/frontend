<?php namespace web\frontend\unittest;

use unittest\TestCase;
use web\frontend\ClassesIn;
use lang\reflect\Package;

class ClassesInTest extends TestCase {

  #[@test]
  public function can_create_with_string() {
    new ClassesIn('web.frontend.unittest.actions');
  }

  #[@test]
  public function can_create_with_package() {
    new ClassesIn(Package::forName('web.frontend.unittest.actions'));
  }

  #[@test]
  public function patterns_sorted_by_length() {
    $delegates= new ClassesIn('web.frontend.unittest.actions');
    $this->assertEquals(
      [
        '#get/blogs/(?<category>[^/]+)/(?<id>[0-9]+)$#',
        '#get/users/(?<id>[^/]+)/avatar$#',
        '#get/users/(?<id>[^/]+)$#',
        '#get/exception$#',
        '#post/users$#',
        '#get/users$#',
        '#get.+$#'
      ],
      array_keys($delegates->patterns)
    );
  }
}