<?php namespace web\frontend\unittest\bundler;

use io\{Files, Folder};
use lang\{Environment, IllegalArgumentException};
use test\verify\Runtime;
use test\{After, Assert, Before, Expect, Test};
use util\URI;
use xp\frontend\Fetch;

class FetchTest {
  const ETAG= '"17e1-5626f327566c0"';

  private $tempDir;

  /** Creates a new random URI */
  private function randomUri(): URI {
    return new URI('https://example.com/?id='.uniqid());
  }

  #[Before]
  public function tempDir() {
    $this->tempDir= new Folder(Environment::tempDir(), uniqid());
    $this->tempDir->create(0777);
  }

  #[Test]
  public function can_create() {
    new Fetch($this->tempDir, false);
  }

  #[Test]
  public function fetches_from_and_caches_revalidating() {
    $uri= $this->randomUri();
    $fixture= (new Fetch($this->tempDir, false))->connecting(function($uri) {
      return new TestConnection(200, ['Content-Length' => 4, 'ETag' => self::ETAG], 'Test');
    });

    $response= $fixture->get($uri, [], false);

    Assert::false($response->cached());
    Assert::equals('Test', $response->read());
    Assert::true($fixture->cache($uri)->exists());
  }

  #[Test]
  public function uses_cache() {
    $uri= $this->randomUri();
    $fixture= new Fetch($this->tempDir, false);

    Files::write($fixture->cache($uri), self::ETAG."\nTest");
    $response= $fixture->get($uri, [], false);

    Assert::true($response->cached());
    Assert::equals('Test', $response->read());
  }

  #[Test]
  public function revalidates_cache() {
    $uri= $this->randomUri();
    $fixture= (new Fetch($this->tempDir, false))->connecting(function($uri) {
      return new TestConnection(200, ['Content-Length' => 4, 'ETag' => self::ETAG], 'Test');
    });

    Files::write($fixture->cache($uri), self::ETAG."\nTest");
    $response= $fixture->get($uri, [], true);

    Assert::false($response->cached());
    Assert::equals('Test', $response->read());
  }

  #[Test]
  public function uses_cache_if_not_modified() {
    $uri= $this->randomUri();
    $fixture= (new Fetch($this->tempDir, false))->connecting(function($uri) {
      return new TestConnection(304, ['ETag' => self::ETAG]);
    });

    Files::write($fixture->cache($uri), self::ETAG."\nTest");
    $response= $fixture->get($uri, [], true);

    Assert::true($response->cached());
    Assert::equals('Test', $response->read());
  }

  #[Test, Expect(class: IllegalArgumentException::class, message: '404 Test')]
  public function throws_on_not_found() {
    $fixture= (new Fetch($this->tempDir, false))->connecting(function($uri) {
      return new TestConnection(404);
    });

    $fixture->get($this->randomUri(), [], true);
  }

  #[Test]
  public function handles_identity_encoding() {
    $fixture= (new Fetch($this->tempDir, false))->connecting(function($uri) {
      return new TestConnection(
        200,
        ['Content-Length' => 12, 'Content-Encoding' => 'identity'],
        'Test'
      );
    });

    Assert::equals('Test', $fixture->get($this->randomUri(), [], false)->read());
  }

  #[Test, Runtime(extensions: ['zlib'])]
  public function handles_gzip_compression() {
    $fixture= (new Fetch($this->tempDir, false))->connecting(function($uri) {
      return new TestConnection(
        200,
        ['Content-Length' => 12, 'Content-Encoding' => 'gzip'],
        gzencode('Test')
      );
    });

    Assert::equals('Test', $fixture->get($this->randomUri(), [], false)->read());
  }

  #[Test, Runtime(extensions: ['brotli'])]
  public function handles_brotli_compression() {
    $fixture= (new Fetch($this->tempDir, false))->connecting(function($uri) {
      return new TestConnection(
        200,
        ['Content-Length' => 12, 'Content-Encoding' => 'br'],
        brotli_compress('Test')
      );
    });

    Assert::equals('Test', $fixture->get($this->randomUri(), [], false)->read());
  }

  #[After]
  public function cleanUp() {
    $this->tempDir->unlink();
  }
}