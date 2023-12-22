<?php namespace web\frontend\unittest;

use lang\reflection\Package;
use test\{Assert, Test};
use web\frontend\HandlersIn;

class HandlersInTest {
  const PACKAGE= 'web.frontend.unittest.actions';

  #[Test]
  public function can_create_with_string() {
    new HandlersIn(self::PACKAGE);
  }

  #[Test]
  public function can_create_with_package() {
    new HandlersIn(new Package(self::PACKAGE));
  }

  #[Test]
  public function patterns_sorted_by_length() {
    $delegates= new HandlersIn(self::PACKAGE);
    Assert::equals(
      [
        '#get/blogs/(?<category>[^/]+)/(?<id>[0-9]+)$#',
        '#get/oauth/(?<tenant>[^/]+)/select/?$#',
        '#get/users/(?<id>[^/]+)/avatar$#',
        '#delete/users/(?<id>[^/]+)$#',
        '#get/users/(?<id>[^/]+)$#',
        '#get/posts/(?<id>[^/]+)$#',
        '#get/blogs/stats$#',
        '#get/blogs/?$#',
        '#post/users$#',
        '#get/users$#',
        '#get/?$#'
      ],
      array_keys($delegates->patterns)
    );
  }
}