<?php namespace web\frontend\unittest;

use unittest\{Assert, Before, Test};
use web\frontend\unittest\actions\Home;
use web\frontend\{Frontend, Templates};
use web\io\{TestInput, TestOutput};
use web\{Request, Response};

class SecurityTest {
  private $templates;

  #[Before]
  public function templates() {
    $this->templates= new class() implements Templates {
      public function write($template, $context, $out) { /* NOOP */ }
    };
  }

  /**
   * Calls fixture's `handle()` method
   *
   * @param  web.frontend.Frontend $fixture
   * @return web.Response
   */
  private function handle($fixture) {
    $req= new Request(new TestInput('GET', '/'));
    $res= new Response(new TestOutput());
    $fixture->handle($req, $res);

    return $res;
  }

  #[Test]
  public function x_content_type_options_header_always_nosniff() {
    $fixture= new Frontend(new Home(), $this->templates);
    $res= $this->handle($fixture);

    Assert::equals('nosniff', $res->headers()['X-Content-Type-Options']);
  }

  #[Test]
  public function x_frame_options_header_default() {
    $fixture= new Frontend(new Home(), $this->templates);
    $res= $this->handle($fixture);

    Assert::equals('DENY', $res->headers()['X-Frame-Options']);
  }

  #[Test]
  public function change_x_frame_options() {
    $fixture= new Frontend(new Home(), $this->templates);
    $fixture->security()->framing('SAMEORIGIN');
    $res= $this->handle($fixture);

    Assert::equals('SAMEORIGIN', $res->headers()['X-Frame-Options']);
  }

  #[Test]
  public function referrer_policy_default() {
    $fixture= new Frontend(new Home(), $this->templates);
    $res= $this->handle($fixture);

    Assert::equals('no-referrer-when-downgrade', $res->headers()['Referrer-Policy']);
  }

  #[Test]
  public function change_referrer_policy() {
    $fixture= new Frontend(new Home(), $this->templates);
    $fixture->security()->referrers('strict-origin');
    $res= $this->handle($fixture);

    Assert::equals('strict-origin', $res->headers()['Referrer-Policy']);
  }

  #[Test]
  public function add_content_security_policy() {
    $fixture= new Frontend(new Home(), $this->templates);
    $fixture->security()->csp([
      'default-src' => '"none"',
      'script-src'  => ['"self"', '"nonce-1234"', 'https://example.com']
    ]);
    $res= $this->handle($fixture);

    Assert::equals(
      'default-src "none"; script-src "self" "nonce-1234" https://example.com',
      $res->headers()['Content-Security-Policy']
    );
  }

  #[Test]
  public function add_report_only_content_security_policy() {
    $fixture= new Frontend(new Home(), $this->templates);
    $fixture->security()->csp(['default-src' => '"none"'], true);
    $res= $this->handle($fixture);

    Assert::equals(
      'default-src "none"',
      $res->headers()['Content-Security-Policy-Report-Only']
    );
  }

  #[Test]
  public function content_security_policy_generates_and_passes_nonce() {
    $fixture= new Frontend(new Home(), newinstance(Templates::class, [], [
      'write' => function($template, $context= [], $out) use(&$result) {
        $result= $context;
      }
    ]));
    $fixture->security()->csp(['script-src' => '"nonce-{{nonce}}"']);
    $res= $this->handle($fixture);

    preg_match('/script-src "nonce-([^"]+)"/', $res->headers()['Content-Security-Policy'], $m);
    Assert::equals(32, strlen($m[1]), 'nonce must consist of 32 bytes "'.$m[1].'"');
    Assert::equals($m[1], $result['nonce'] ?? null, 'nonce must appear in content');
  }

  #[Test]
  public function nonce_generated_is_unique_for_every_request() {
    $fixture= new Frontend(new Home(), newinstance(Templates::class, [], [
      'write' => function($template, $context= [], $out) use(&$result) {
        $result= $context;
      }
    ]));
    $fixture->security()->csp(['script-src' => '"nonce-{{nonce}}"']);

    Assert::notEquals(
      $this->handle($fixture)->headers()['Content-Security-Policy'],
      $this->handle($fixture)->headers()['Content-Security-Policy']
    );
  }
}