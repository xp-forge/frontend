<?php namespace web\frontend\unittest;

use lang\IllegalArgumentException;
use unittest\{Expect, Test, TestCase};
use web\frontend\unittest\actions\Users;
use web\frontend\{Frontend, Templates};

class FrontendTest extends TestCase {
  private $templates;

  /** @return void */
  public function setUp() {
    $this->templates= new class() implements Templates {
      public function write($template, $context, $out) { /* NOOP */ }
    };
  }

  #[Test]
  public function can_create() {
    new Frontend(new Users(), $this->templates);
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function first_argument_must_be_object() {
    new Frontend(null, $this->templates);
  }
}