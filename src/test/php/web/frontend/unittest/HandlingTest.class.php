<?php namespace web\frontend\unittest;

use unittest\TestCase;
use web\Error;
use web\frontend\Frontend;
use web\frontend\Templates;
use web\frontend\View;
use web\io\TestInput;
use web\io\TestOutput;
use web\Request;
use web\Response;

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
  public function unsupported_verb() {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context, $out) { /* NOOP */ }
    ]));

    $this->handle($fixture, 'PATCH', '/users/1', 'username=@illegal@');
  }

  #[@test, @expect(Error::class)]
  public function exceptions_result_in_internal_server_error() {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context, $out) { /* NOOP */ }
    ]));

    $this->handle($fixture, 'POST', '/users', 'username=@illegal@');
  }

  #[@test]
  public function template_determined_from_view() {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context= [], $out) use(&$result) {
        $result= $template;
      }
    ]));

    $this->handle($fixture, 'GET', '/users/1000');
    $this->assertEquals('no-user', $result);
  }

  #[@test]
  public function redirect() {
    $fixture= new Frontend(new Users(), newinstance(Templates::class, [], [
      'write' => function($template, $context= [], $out) use(&$result) {
        $result= $template;
      }
    ]));

    $res= $this->handle($fixture, 'GET', '/users/0');
    $this->assertEquals('/users/1', $res->headers()['Location']);
  }
}