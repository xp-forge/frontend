<?php namespace web\frontend\unittest;

use lang\IndexOutOfBoundsException;
use test\Assert;
use test\{Expect, Test, TestCase, Values};
use web\frontend\unittest\actions\{Blogs, Home, Select, Users};
use web\frontend\{Frontend, Templates, View};
use web\io\{TestInput, TestOutput};
use web\{Error, Request, Response};

class HandlingTest {

  /**
   * Calls fixture's `handle()` method
   *
   * @param  web.frontend.Frontend $fixture
   * @param  string $method
   * @param  string $uri
   * @param  string $body
   * @return web.Response
   */
  private function handle($fixture, $method, $uri, $headers= [], $body= '') {
    if (null !== $body) {
      $headers['Content-Type']= 'application/x-www-form-urlencoded';
      $headers['Content-Length']= strlen($body);
    }

    $req= new Request(new TestInput($method, $uri, $headers, $body));
    $res= new Response(new TestOutput());
    $fixture->handle($req, $res);

    return $res;
  }

  /**
   * Assertion helper to compare template engine context
   *
   * @param  [:var] $expected
   * @param  [:var] $actual
   * @return void
   * @throws unittest.AssertionFailedError
   */
  private function assertContext($expected, $actual) {
    $actual['request']= ['params' => $actual['request']->params()];
    Assert::equals($expected, $actual);
  }

  #[Test]
  public function template_name_inferred_from_class_name() {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context, $out) use(&$result) {
        $result= $template;
      }
    ]));

    $this->handle($fixture, 'GET', '/users/1');
    Assert::equals('users', $result);
  }

  #[Test]
  public function template_rendered() {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context, $out) {
        $out->write('Test');
      }
    ]));

    $res= $this->handle($fixture, 'GET', '/users/1');
    Assert::notEquals(false, strpos($res->output()->bytes(), 'Test'));
  }

  #[Test]
  public function extract_path_segment() {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context, $out) use(&$result) {
        $result= $context;
      }
    ]));

    $this->handle($fixture, 'GET', '/users/1');
    $this->assertContext(
      ['id' => 1, 'name' => 'Test', 'request' => [
        'params' => []
      ]],
      $result
    );
  }

  #[Test, Values(['/users?max=100&start=1', '/users?start=1&max=100'])]
  public function use_request_parameters($uri) {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context, $out) use(&$result) {
        $result= $context;
      }
    ]));

    $return= ['start' => '1', 'max' => '100', 'list' => []];
    $this->handle($fixture, 'GET', $uri);
    $this->assertContext(
      array_merge($return, ['request' => [
        'params' => ['max' => '100', 'start' => '1']
      ]]),
      $result
    );
  }

  #[Test]
  public function omit_optional_request_parameter() {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context, $out) use(&$result) {
        $result= $context;
      }
    ]));

    $return= ['start' => 0, 'max' => -1, 'list' => [['id' => 1, 'name' => 'Test']]];
    $this->handle($fixture, 'GET', '/users');
    $this->assertContext(
      array_merge($return, ['request' => [
        'params' => []
      ]]),
      $result
    );
  }

  #[Test]
  public function post() {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context, $out) use(&$result) {
        $result= $context;
      }
    ]));

    $return= ['created' => 2];
    $this->handle($fixture, 'POST', '/users', [], 'username=New');
    $this->assertContext(
      array_merge($return, ['request' => [
        'params' => ['username' => 'New']
      ]]),
      $result
    );
  }

  #[Test, Expect(class: Error::class, message: '/Cannot route PATCH requests to .+/')]
  public function unsupported_route() {
    $fixture= new Frontend(new Users(), new class() implements Templates {
      public function write($template, $context, $out) { /* NOOP */ }
    });

    $this->handle($fixture, 'PATCH', '/users/1', [], '(irrelevant)');
  }

  #[Test, Expect(class: Error::class, message: '/Illegal username ".+"/')]
  public function exceptions_result_in_internal_server_error() {
    $fixture= new Frontend(new Users(), new class() implements Templates {
      public function write($template, $context, $out) { /* NOOP */ }
    });

    $this->handle($fixture, 'POST', '/users', [], 'username=@illegal@');
  }

  #[Test]
  public function template_determined_from_view() {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context, $out) use(&$result) {
        $result= $template;
      }
    ]));

    $this->handle($fixture, 'GET', '/users/1000');
    Assert::equals('no-user', $result);
  }

  #[Test]
  public function can_set_status() {
    $fixture= new Frontend(new Users(), new class() implements Templates {
      public function write($template, $context, $out) { /* NOOP */ }
    });

    $res= $this->handle($fixture, 'GET', '/users/1000');
    Assert::equals(404, $res->status());
  }

  #[Test]
  public function can_set_header() {
    $fixture= new Frontend(new Users(), new class() implements Templates {
      public function write($template, $context, $out) { /* NOOP */ }
    });

    $res= $this->handle($fixture, 'GET', '/users/1');
    Assert::equals('1', $res->headers()['X-User-ID']);
  }

  #[Test]
  public function redirect() {
    $fixture= new Frontend(new Users(), new class() implements Templates {
      public function write($template, $context, $out) { /* NOOP */ }
    });

    $res= $this->handle($fixture, 'GET', '/users/0');
    Assert::equals([302, '/users/1'], [$res->status(), $res->headers()['Location']]);
  }

  #[Test]
  public function path_segments() {
    $fixture= new Frontend(new Blogs(), newinstance(Templates::class, [], [
      'write' => function($template, $context, $out) use(&$result) {
        $result= $context;
      }
    ]));

    $return= ['category' => 'development', 'article' => 1];
    $res= $this->handle($fixture, 'GET', '/blogs/development/1');
    $this->assertContext($return + ['request' => ['params' => []]], $result);
  }

  #[Test]
  public function path_segments_in_handler() {
    $fixture= new Frontend(new Select(), newinstance(Templates::class, [], [
      'write' => function($template, $context, $out) use(&$result) {
        $result= $context;
      }
    ]));

    $return= ['tenant' => '8555b51d-6f6d-42cd-843c-daa1c25fd5ee'];
    $res= $this->handle($fixture, 'GET', '/oauth/8555b51d-6f6d-42cd-843c-daa1c25fd5ee/select');
    $this->assertContext($return + ['request' => ['params' => []]], $result);
  }

  #[Test]
  public function globals_included() {
    $globals= ['base' => '/', 'fingerprint' => '99b3825'];
    $fixture= new Frontend(new Home(), newinstance(Templates::class, [], [
      'write' => function($template, $context, $out) use(&$result) {
        $result= $context;
      }
    ]), $globals);

    $this->handle($fixture, 'GET', '/');
    $this->assertContext($globals + ['home' => null, 'request' => ['params' => []]], $result);
  }

  #[Test]
  public function globals_overwritten_by_context() {
    $globals= ['home' => '(overwritten)'];
    $fixture= new Frontend(new Home(), newinstance(Templates::class, [], [
      'write' => function($template, $context, $out) use(&$result) {
        $result= $context;
      }
    ]), $globals);

    $this->handle($fixture, 'GET', '/');
    $this->assertContext(['home' => null, 'request' => ['params' => []]], $result);
  }

  #[Test]
  public function accessing_request() {
    $fixture= new Frontend(new Home(), newinstance(Templates::class, [], [
      'write' => function($template, $context, $out) use(&$result) {
        $result= $context;
      }
    ]));

    $this->handle($fixture, 'GET', '/', ['Cookie' => 'test=Works']);
    $this->assertContext(['home' => 'Works', 'request' => ['params' => []]], $result);
  }

  #[Test]
  public function exceptions_are_wrapped_in_internal_server_errors() {
    $fixture= new Frontend(new Users(), new class() implements Templates {
      public function write($template, $context, $out) { /* NOOP */ }
    });

    try {
      $this->handle($fixture, 'GET', '/users/1/avatar');
      $this->fail('No exception raised', null, Error::class);
    } catch (Error $expected) {
      Assert::equals(500, $expected->status());
      Assert::true((bool)preg_match('/Undefined.+avatar/', $expected->getMessage()));
      Assert::instance(IndexOutOfBoundsException::class, $expected->getCause());
    }
  }

  #[Test]
  public function errors_are_transmitted_as_is() {
    $fixture= new Frontend(new Users(), new class() implements Templates {
      public function write($template, $context, $out) { /* NOOP */ }
    });

    try {
      $this->handle($fixture, 'GET', '/users/42/avatar');
      $this->fail('No exception raised', null, Error::class);
    } catch (Error $expected) {
      Assert::equals(404, $expected->status());
      Assert::equals('No such user 42', $expected->getMessage());
      Assert::null($expected->getCause());
    }
  }

  #[Test]
  public function content_type_headers() {
    $fixture= new Frontend(new Users(), new class() implements Templates {
      public function write($template, $context, $out) { /* NOOP */ }
    });

    $res= $this->handle($fixture, 'GET', '/users');
    Assert::equals('text/html; charset='.\xp::ENCODING, $res->headers()['Content-Type']);
    Assert::equals('nosniff', $res->headers()['X-Content-Type-Options']);
  }

  #[Test]
  public function defaults_to_no_caching() {
    $fixture= new Frontend(new Blogs(), new class() implements Templates {
      public function write($template, $context, $out) { /* NOOP */ }
    });

    $res= $this->handle($fixture, 'GET', '/blogs');
    Assert::equals('no-cache', $res->headers()['Cache-Control']);
  }

  #[Test]
  public function view_can_set_caching() {
    $fixture= new Frontend(new Blogs(), new class() implements Templates {
      public function write($template, $context, $out) { /* NOOP */ }
    });

    $res= $this->handle($fixture, 'GET', '/blogs/development/1');
    Assert::equals('max-age=2419200, must-revalidate', $res->headers()['Cache-Control']);
  }

  #[Test]
  public function view_can_return_null() {
    $fixture= new Frontend(new Blogs(), newinstance(Templates::class, [], [
      'write' => function($template, $context, $out) use(&$result) {
        $result= $context;
      }
    ]));

    $this->handle($fixture, 'GET', '/blogs/stats');
    $this->assertContext(['request' => ['params' => []]], $result);
  }

  #[Test]
  public function head_handled_by_get() {
    $handler= newinstance(Users::class, [], [
      '#[Get] handler' => function() use(&$invoked) { return $invoked= true; }
    ]);
    $fixture= new Frontend($handler, new class() implements Templates {
      public function write($template, $context, $out) { /* NOOP */ }
    });

    $this->handle($fixture, 'HEAD', '/');
    Assert::true($invoked);
  }

  #[Test]
  public function head_explictely_defined() {
    $handler= newinstance(Users::class, [], [
      '#[Head] handler' => function() use(&$invoked) { return $invoked= true; }
    ]);
    $fixture= new Frontend($handler, new class() implements Templates {
      public function write($template, $context, $out) { /* NOOP */ }
    });

    $this->handle($fixture, 'HEAD', '/');
    Assert::true($invoked);
  }

  #[Test]
  public function head_request_does_not_send_response() {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context, $out) {
        $out->write('Test');
      }
    ]));

    $res= $this->handle($fixture, 'HEAD', '/users/1');
    Assert::equals("\r\n\r\n", strstr($res->output()->bytes(), "\r\n\r\n"));
  }
}