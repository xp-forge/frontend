<?php namespace web\frontend\unittest;

use unittest\TestCase;
use web\frontend\{Frontend, Templates};
use web\{Request, Response, Error};
use web\io\{TestInput, TestOutput};

class FrontendTest extends TestCase {
  private $templates;

  /** @return void */
  public function setUp() {
    $this->templates= new class() implements Templates {
      public function write($template, $context= [], $out) { /* NOOP */ }
    };
  }

  /**
   * Calls fixture's `handle()` method
   *
   * @param  web.frontend.Frontend $fixture
   * @param  string $method
   * @param  string $uri
   * @param  string $body
   * @return web.Response
   */
  private function handle($fixture, $method, $uri, $body= null) {
    if (null === $body) {
      $headers= [];
    } else {
      $headers= ['Content-Type' => 'application/x-www-form-urlencoded', 'Content-Length' => strlen($body)];
    }

    $req= new Request(new TestInput($method, $uri, $headers, $body));
    $res= new Response(new TestOutput());
    $fixture->handle($req, $res);

    return $res;
  }

  #[@test]
  public function can_create() {
    new Frontend(new Users(), $this->templates);
  }

  #[@test]
  public function template_name_inferred_from_class_name() {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context= [], $out) use(&$result) {
        $result= $template;
      }
    ]));

    $this->handle($fixture, 'GET', '/users/1');
    $this->assertEquals('users', $result);
  }

  #[@test]
  public function template_rendered() {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context= [], $out) {
        $out->write('Test');
      }
    ]));

    $res= $this->handle($fixture, 'GET', '/users/1');
    $this->assertNotEquals(false, strpos($res->output()->bytes(), 'Test'));
  }

  #[@test]
  public function extract_path_segment() {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context= [], $out) use(&$result) {
        $result= $context;
      }
    ]));

    $this->handle($fixture, 'GET', '/users/1');
    $this->assertEquals(
      ['id' => 1, 'name' => 'Test', 'base' => '', 'request' => [
        'params' => [],
        'values' => []
      ]],
      $result
    );
  }

  #[@test, @values(['/users?max=100&start=1', '/users?start=1&max=100'])]
  public function use_request_parameters($uri) {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context= [], $out) use(&$result) {
        $result= $context;
      }
    ]));

    $return= ['start' => '1', 'max' => '100', 'list' => []];
    $this->handle($fixture, 'GET', $uri);
    $this->assertEquals(
      array_merge($return, ['base' => '', 'request' => [
        'params' => ['max' => '100', 'start' => '1'],
        'values' => []
      ]]),
      $result
    );
  }

  #[@test]
  public function omit_optional_request_parameter() {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context= [], $out) use(&$result) {
        $result= $context;
      }
    ]));

    $return= ['start' => 0, 'max' => -1, 'list' => [['id' => 1, 'name' => 'Test']]];
    $this->handle($fixture, 'GET', '/users');
    $this->assertEquals(
      array_merge($return, ['base' => '', 'request' => [
        'params' => [],
        'values' => []
      ]]),
      $result
    );
  }

  #[@test]
  public function post() {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context= [], $out) use(&$result) {
        $result= $context;
      }
    ]));

    $return= ['created' => 2];
    $this->handle($fixture, 'POST', '/users', 'username=New');
    $this->assertEquals(
      array_merge($return, ['base' => '', 'request' => [
        'params' => ['username' => 'New'],
        'values' => []
      ]]),
      $result
    );
  }

  #[@test, @expect(Error::class)]
  public function exceptions_result_in_internal_server_error() {
    $fixture= new Frontend(new Users(), $this->templates);

    $this->handle($fixture, 'GET', '/users/no.such.user');
  }
}