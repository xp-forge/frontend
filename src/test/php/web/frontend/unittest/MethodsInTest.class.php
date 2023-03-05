<?php namespace web\frontend\unittest;

use test\Assert;
use test\{Test, TestCase};
use web\frontend\MethodsIn;
use web\frontend\unittest\actions\Users;

class MethodsInTest {

  #[Test]
  public function can_create() {
    new MethodsIn(new Users());
  }

  #[Test]
  public function patterns_sorted_by_length() {
    $delegates= new MethodsIn(new Users());
    Assert::equals(
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