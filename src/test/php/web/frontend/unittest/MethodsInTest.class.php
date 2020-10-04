<?php namespace web\frontend\unittest;

use unittest\{Test, TestCase};
use web\frontend\MethodsIn;
use web\frontend\unittest\actions\Users;

class MethodsInTest extends TestCase {

  #[Test]
  public function can_create() {
    new MethodsIn(new Users());
  }

  #[Test]
  public function patterns_sorted_by_length() {
    $delegates= new MethodsIn(new Users());
    $this->assertEquals(
      [
        '#get/users/(?<id>[^/]+)/avatar$#',
        '#get/users/(?<id>[^/]+)$#',
        '#post/users$#',
        '#get/users$#',
      ],
      array_keys($delegates->patterns)
    );
  }
}