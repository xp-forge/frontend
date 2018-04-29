<?php namespace web\frontend\unittest;

use unittest\TestCase;
use web\frontend\Frontend;
use web\frontend\Templates;
use lang\IllegalArgumentException;
use web\frontend\unittest\actions\Users;

class FrontendTest extends TestCase {
  private $templates;

  /** @return void */
  public function setUp() {
    $this->templates= newinstance(Templates::class, [], [
      'write' => function($template, $context, $out) { /* NOOP */ }
    ]);
  }

  #[@test]
  public function can_create() {
    new Frontend(new Users(), $this->templates);
  }

  #[@test, @expect(IllegalArgumentException::class)]
  public function first_argument_must_be_object() {
    new Frontend(null, $this->templates);
  }
}