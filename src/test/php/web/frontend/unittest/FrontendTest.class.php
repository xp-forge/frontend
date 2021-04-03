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

  #[Test]
  public function globals_empty_by_default() {
    $this->assertEquals([], (new Frontend(new Users(), $this->templates))->globals);
  }

  #[Test]
  public function globals_passed_to_constructor() {
    $globals= ['base' => '/', 'fingerprint' => '99b3825'];
    $this->assertEquals($globals, (new Frontend(new Users(), $this->templates, $globals))->globals);
  }

  #[Test]
  public function base_passed_to_constructor() {
    $this->assertEquals(['base' => ''], (new Frontend(new Users(), $this->templates, '/'))->globals);
  }
}