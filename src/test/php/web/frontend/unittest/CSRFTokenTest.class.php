<?php namespace web\frontend\unittest;

use unittest\TestCase;
use web\frontend\unittest\actions\Users;
use web\frontend\{Frontend, Templates};
use web\io\{TestInput, TestOutput};
use web\{Error, Request, Response};

class CSRFTokenTest extends TestCase {
  const TOKEN= 'd6cb/ttqAu0iXrj9ycQrGN9lo';

  private $fixture;

  /** @return void */
  public function setUp() {
    $this->fixture= new Frontend(new Users(), new class() implements Templates {
      public function write($template, $context, $out) { /* NOOP */ }
    });
  }

  /**
   * Executes a given request
   *
   * @param  string $method
   * @param  string $uri
   * @param  ?string $payload
   * @return void
   * @throws web.Error
   */
  private function execute($method, $uri, $payload= null) {
    $headers= $payload ? ['Content-Type' => 'application/x-www-form-urlencoded'] : [];

    $req= new Request(new TestInput($method, $uri, $headers, $payload));
    $res= new Response(new TestOutput());

    $this->fixture->handle($req->pass('token', self::TOKEN), $res);
  }

  #[Test]
  public function validated() {
    $this->execute('POST', '/users', 'token='.self::TOKEN.'&username=test');
  }

  #[Test]
  public function not_validated_for_get_requests() {
    $this->execute('GET', '/users');
  }

  #[Test, Expect(class: Error::class, withMessage: '/Missing CSRF token for .+Users::create/')]
  public function raises_error_when_missing() {
    $this->execute('POST', '/users', 'username=test');
  }

  #[Test, Expect(class: Error::class, withMessage: '/Missing CSRF token for .+Users::create/')]
  public function raises_error_when_incorrect() {
    $this->execute('POST', '/users', 'token=INCORRECT&username=test');
  }
}