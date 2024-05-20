<?php namespace web\frontend\unittest;

use test\{Assert, Test};
use util\URI;
use web\frontend\{Frontend, Templates, Get, Param, View};
use web\io\{TestInput, TestOutput};
use web\{Application, Environment, Request, Response};

class DispatchingTest {
  private $templates;

  /**
   * Calls fixture's `handle()` method
   *
   * @param  object $handler
   * @param  string $method
   * @param  string $uri
   * @return string
   */
  private function handle($handler, $method, $uri) {
    $req= new Request(new TestInput($method, $uri));
    $res= new Response((new TestOutput())->buffered());
    $app= new class(new Environment('test'), $handler) extends Application {
      private $handler;

      public function __construct(Environment $environment, $handler) {
        parent::__construct($environment);
        $this->handler= $handler;
      }

      public function routes() {
        return [
          '/static' => function($req, $res) {
            $res->send("Static {$req->uri()->path()}", 'text/plain');
          },
          '/'       => new Frontend($this->handler, new class() implements Templates {
            public function write($template, $context, $out) {
              $out->write("Template {$template}");
            }
          }),
        ];
      }
    };

    foreach ($app->service($req, $res) ?? [] as $_) { }
    return $res->output()->body();
  }

  #[Test]
  public function to_route() {
    $fixture= new class() {
      #[Get]
      public function dispatcher() {
        return View::dispatch('/static/file');
      }
    };

    Assert::equals('Static /static/file', $this->handle($fixture, 'GET', '/'));
  }

  #[Test]
  public function internally() {
    $fixture= new class() {
      #[Get('/users')]
      public function users() {
        return View::named('users');
      }

      #[Get]
      public function dispatcher() {
        return View::dispatch('/users');
      }
    };

    Assert::equals('Template users', $this->handle($fixture, 'GET', '/'));
  }

  #[Test]
  public function with_params() {
    $fixture= new class() {
      #[Get('/users')]
      public function users(
        #[Param]
        string $test
      ) {
        return View::named("users-{$test}");
      }

      #[Get]
      public function dispatcher() {
        return View::dispatch('/users', ['test' => 'success']);
      }
    };

    Assert::equals('Template users-success', $this->handle($fixture, 'GET', '/'));
  }

  #[Test]
  public function no_dispatch() {
    $fixture= new class() {
      #[Get]
      public function empty() {
        return View::named('test');
      }
    };

    Assert::equals('Template test', $this->handle($fixture, 'GET', '/'));
  }
}