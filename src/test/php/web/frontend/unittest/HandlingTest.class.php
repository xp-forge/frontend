<?php namespace web\frontend\unittest;

use lang\IndexOutOfBoundsException;
use unittest\{Expect, Test, TestCase, Values};
use web\frontend\unittest\actions\{Blogs, Home, Users};
use web\frontend\{Frontend, Templates, View};
use web\io\{TestInput, TestOutput};
use web\{Error, Request, Response};

class HandlingTest extends TestCase {

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
    $this->assertEquals($expected, $actual);
  }

  #[Test]
  public function template_name_inferred_from_class_name() {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context= [], $out) use(&$result) {
        $result= $template;
      }
    ]));

    $this->handle($fixture, 'GET', '/users/1');
    $this->assertEquals('users', $result);
  }

  #[Test]
  public function template_rendered() {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context= [], $out) {
        $out->write('Test');
      }
    ]));

    $res= $this->handle($fixture, 'GET', '/users/1');
    $this->assertNotEquals(false, strpos($res->output()->bytes(), 'Test'));
  }

  #[Test]
  public function extract_path_segment() {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context= [], $out) use(&$result) {
        $result= $context;
      }
    ]));

    $this->handle($fixture, 'GET', '/users/1');
    $this->assertContext(
      ['id' => 1, 'name' => 'Test', 'base' => '', 'request' => [
        'params' => []
      ]],
      $result
    );
  }

  #[Test, Values(['/users?max=100&start=1', '/users?start=1&max=100'])]
  public function use_request_parameters($uri) {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context= [], $out) use(&$result) {
        $result= $context;
      }
    ]));

    $return= ['start' => '1', 'max' => '100', 'list' => []];
    $this->handle($fixture, 'GET', $uri);
    $this->assertContext(
      array_merge($return, ['base' => '', 'request' => [
        'params' => ['max' => '100', 'start' => '1']
      ]]),
      $result
    );
  }

  #[Test]
  public function omit_optional_request_parameter() {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context= [], $out) use(&$result) {
        $result= $context;
      }
    ]));

    $return= ['start' => 0, 'max' => -1, 'list' => [['id' => 1, 'name' => 'Test']]];
    $this->handle($fixture, 'GET', '/users');
    $this->assertContext(
      array_merge($return, ['base' => '', 'request' => [
        'params' => []
      ]]),
      $result
    );
  }

  #[Test]
  public function post() {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context= [], $out) use(&$result) {
        $result= $context;
      }
    ]));

    $return= ['created' => 2];
    $this->handle($fixture, 'POST', '/users', [], 'username=New');
    $this->assertContext(
      array_merge($return, ['base' => '', 'request' => [
        'params' => ['username' => 'New']
      ]]),
      $result
    );
  }

  #[Test, Expect(['class' => Error::class, 'withMessage'=> '/Method PATCH not supported by any delegate/'])]
  public function unsupported_verb() {
    $fixture= new Frontend(new Users(), new class() implements Templates {
      public function write($template, $context, $out) { /* NOOP */ }
    });

    $this->handle($fixture, 'PATCH', '/users/1', [], 'username=@illegal@');
  }

  #[Test, Expect(['class' => Error::class, 'withMessage'=> '/Illegal username ".+"/'])]
  public function exceptions_result_in_internal_server_error() {
    $fixture= new Frontend(new Users(), new class() implements Templates {
      public function write($template, $context, $out) { /* NOOP */ }
    });

    $this->handle($fixture, 'POST', '/users', [], 'username=@illegal@');
  }

  #[Test]
  public function template_determined_from_view() {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context= [], $out) use(&$result) {
        $result= $template;
      }
    ]));

    $this->handle($fixture, 'GET', '/users/1000');
    $this->assertEquals('no-user', $result);
  }

  #[Test]
  public function can_set_status() {
    $fixture= new Frontend(new Users(), new class() implements Templates {
      public function write($template, $context, $out) { /* NOOP */ }
    });

    $res= $this->handle($fixture, 'GET', '/users/1000');
    $this->assertEquals(404, $res->status());
  }

  #[Test]
  public function can_set_header() {
    $fixture= new Frontend(new Users(), new class() implements Templates {
      public function write($template, $context, $out) { /* NOOP */ }
    });

    $res= $this->handle($fixture, 'GET', '/users/1');
    $this->assertEquals('1', $res->headers()['X-User-ID']);
  }

  #[Test]
  public function redirect() {
    $fixture= new Frontend(new Users(), new class() implements Templates {
      public function write($template, $context, $out) { /* NOOP */ }
    });

    $res= $this->handle($fixture, 'GET', '/users/0');
    $this->assertEquals([302, '/users/1'], [$res->status(), $res->headers()['Location']]);
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
    $this->assertContext(
      array_merge($return, ['base' => '', 'request' => [
        'params' => []
      ]]),
      $result
    );
  }

  #[Test]
  public function accessing_request() {
    $fixture= new Frontend(new Home(), newinstance(Templates::class, [], [
      'write' => function($template, $context= [], $out) use(&$result) {
        $result= $context;
      }
    ]));

    $this->handle($fixture, 'GET', '/', ['Cookie' => 'test=Works']);
    $this->assertContext(
      ['home' => 'Works', 'base' => '', 'request' => [
        'params' => []
      ]],
      $result
    );
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
      $this->assertEquals(500, $expected->status());
      $this->assertTrue((bool)preg_match('/Undefined.+avatar/', $expected->getMessage()));
      $this->assertInstanceOf(IndexOutOfBoundsException::class, $expected->getCause());
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
      $this->assertEquals(404, $expected->status());
      $this->assertEquals('No such user 42', $expected->getMessage());
      $this->assertNull($expected->getCause());
    }
  }

  #[Test]
  public function content_type_headers() {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context= [], $out) {
        // NOOP
      }
    ]));

    $res= $this->handle($fixture, 'GET', '/users');
    $this->assertEquals('text/html; charset=utf-8', $res->headers()['Content-Type']);
    $this->assertEquals('nosniff', $res->headers()['X-Content-Type-Options']);
  }

  #[Test]
  public function defaults_to_no_caching() {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context= [], $out) {
        // NOOP
      }
    ]));

    $res= $this->handle($fixture, 'GET', '/users');
    $this->assertEquals('no-cache', $res->headers()['Cache-Control']);
  }

  #[Test]
  public function view_can_set_caching() {
    $users= new Users();
    $users->list[1]= ['id' => 1, 'name' => 'Test', 'avatar' => 'PNG...'];
    $fixture= new Frontend($users, newinstance(Templates::class, [], [
      'write' => function($template, $context= [], $out) {
        // NOOP
      }
    ]));

    $res= $this->handle($fixture, 'GET', '/users/1/avatar');
    $this->assertEquals('max-age=2419200, must-revalidate', $res->headers()['Cache-Control']);
  }
}