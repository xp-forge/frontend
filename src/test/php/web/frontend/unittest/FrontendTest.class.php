<?php namespace web\frontend\unittest;

use lang\IllegalArgumentException;
use unittest\{Assert, Before, Expect, Test};
use web\frontend\unittest\actions\Users;
use web\frontend\{Frontend, Exceptions, RaiseErrors, Templates};

class FrontendTest {
  private $templates;

  #[Before]
  public function templates() {
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
    Assert::equals([], (new Frontend(new Users(), $this->templates))->globals);
  }

  #[Test]
  public function globals_passed_to_constructor() {
    $globals= ['base' => '/', 'fingerprint' => '99b3825'];
    Assert::equals($globals, (new Frontend(new Users(), $this->templates, $globals))->globals);
  }

  #[Test]
  public function base_passed_to_constructor() {
    Assert::equals(['base' => ''], (new Frontend(new Users(), $this->templates, '/'))->globals);
  }

  #[Test]
  public function raises_errors_by_default() {
    Assert::instance(RaiseErrors::class, (new Frontend(new Users(), $this->templates))->errors());
  }

  #[Test]
  public function changed_exception_handling() {
    $h= new Exceptions();
    Assert::equals($h, (new Frontend(new Users(), $this->templates))->handling($h)->errors());
  }
}