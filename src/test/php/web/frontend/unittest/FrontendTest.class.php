<?php namespace web\frontend\unittest;

use lang\Error;
use test\{Assert, Before, Expect, Test, Values};
use web\frontend\unittest\actions\Users;
use web\frontend\{Delegate, Exceptions, Frontend, RaiseErrors, Security, Templates, MethodsIn};

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

  #[Test, Expect(Error::class)]
  public function first_argument_must_be_object() {
    new Frontend(null, $this->templates);
  }

  #[Test]
  public function globals_empty_by_default() {
    Assert::equals([], (new Frontend(new Users(), $this->templates))->globals);
  }

  #[Test]
  public function globals_passed_to_constructor() {
    $globals= ['base' => '', 'fingerprint' => '99b3825'];
    Assert::equals($globals, (new Frontend(new Users(), $this->templates, $globals))->globals);
  }

  #[Test, Values([['/', ''], ['/test', '/test'], ['/test/', '/test']])]
  public function base_passed_to_constructor($arg, $base) {
    Assert::equals(['base' => $base], (new Frontend(new Users(), $this->templates, $arg))->globals);
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

  #[Test]
  public function delegates() {
    $delegates= new MethodsIn(new Users());
    Assert::equals($delegates, (new Frontend($delegates, $this->templates))->delegates());
  }

  #[Test]
  public function delegate_to_instance() {
    $instance= new Users();
    Assert::equals(new MethodsIn($instance), (new Frontend($instance, $this->templates))->delegates());
  }

  #[Test]
  public function templating() {
    Assert::equals($this->templates, (new Frontend(new Users(), $this->templates))->templates());
  }

  #[Test]
  public function security() {
    $s= new Security();
    Assert::equals($s, (new Frontend(new Users(), $this->templates))->enacting($s)->security());
  }

  #[Test]
  public function all_target() {
    $users= new Users();
    Assert::equals(
      [new Delegate($users, 'all'), ['get/users']],
      (new Frontend($users, $this->templates))->target('get', '/users')
    );
  }

  #[Test]
  public function find_target() {
    $users= new Users();
    Assert::equals(
      [new Delegate($users, 'find'), [0 => 'get/users/me', 1 => 'me', 'id' => 'me']],
      (new Frontend($users, $this->templates))->target('get', '/users/me')
    );
  }
}