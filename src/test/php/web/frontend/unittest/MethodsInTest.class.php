<?php namespace web\frontend\unittest;

use unittest\TestCase;
use web\frontend\MethodsIn;
use web\frontend\unittest\actions\Users;

class MethodsInTest extends TestCase {

  #[@test]
  public function can_create() {
    new MethodsIn(new Users());
  }

  #[@test]
  public function patterns_sorted_by_length() {
    $delegates= new MethodsIn(new Users());
    $this->assertEquals(
      [
        '#get/users/(?<id>[^/]+)/avatar$#',
        '#get/users/(?<id>[^/]+)$#',
        '#get/exception$#',
        '#post/users$#',
        '#get/users$#'
      ],
      array_keys($delegates->patterns)
    );
  }
}