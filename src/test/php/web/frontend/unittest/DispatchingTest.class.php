<?php namespace web\frontend\unittest;

use test\{Assert, Test};
use util\URI;
use web\frontend\{Frontend, Templates, View};
use web\io\{TestInput, TestOutput};
use web\{Request, Response};

class DispatchingTest {
  private $templates;

  /**
   * Calls fixture's `handle()` method
   *
   * @param  object $handler
   * @param  string $method
   * @param  string $uri
   * @return ?iterable
   */
  private function handle($handler, $method, $uri) {
    $req= new Request(new TestInput($method, $uri));
    $res= new Response(new TestOutput());
    $templates= new class() implements Templates {
      public function write($template, $context, $out) { /* NOOP */ }
    };

    return (new Frontend($handler, $templates))->handle($req, $res);
  }

  #[Test]
  public function dispatch() {
    $fixture= new class() {
      #[Get]
      public function dispatcher() {
        return View::dispatch('/users');
      }
    };

    Assert::equals(new URI('http://localhost/users'), $this->handle($fixture, 'GET', '/')->uri());
  }

  #[Test]
  public function with_params() {
    $fixture= new class() {
      #[Get]
      public function dispatcher() {
        return View::dispatch('/users', ['test' => 'success']);
      }
    };

    Assert::equals(new URI('http://localhost/users?test=success'), $this->handle($fixture, 'GET', '/')->uri());
  }

  #[Test]
  public function no_dispatch() {
    $fixture= new class() {
      #[Get]
      public function empty() {
        // No implementation
      }
    };

    Assert::null($this->handle($fixture, 'GET', '/'));
  }
}