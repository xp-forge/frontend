<?php namespace web\frontend\unittest;

use io\{File, Files, Folder};
use lang\Environment;
use test\{After, Assert, Test, Values};
use web\frontend\AssetsFrom;
use web\io\{TestInput, TestOutput};
use web\{Request, Response};

class AssetsFromTest {
  const CONTENTS = 'body { color: red; }';
  const COMPRESSED = "^_\x8B>^H^\x002d_...";

  private $remove= [];

  /**
   * Creates a temporary folder
   *
   * @param  [:string] $files
   * @return io.Folder
   */
  private function folderWith($files) {
    $f= new Folder(Environment::tempDir(), uniqid());
    $f->create();
    foreach ($files as $name => $content) {
      Files::write(new File($f, $name), $content);
    }
    return $this->remove[]= $f;
  }

  /**
   * Serve a GET request from the specified files
   *
   * @param  web.frontend.AssetsFrom $assets
   * @param  string $path
   * @param  [:string] $headers
   * @return web.Response
   */
  private function serve($assets, $path, $headers= []) {
    $req= new Request(new TestInput('GET', $path, $headers));
    $res= new Response(new TestOutput());

    try {
      foreach ($assets->handle($req, $res) ?? [] as $_) { }
      return $res;
    } finally {
      $res->end();
    }
  }

  /**
   * Assertion helper
   *
   * @param  string $bytes
   * @param  web.Response $res
   * @throws unittest.AssertionFailedError
   * @return void
   */
  private function assertFile($bytes, $res) {
    Assert::equals("\r\n\r\n$bytes", strstr($res->output()->bytes(), "\r\n\r\n"));
  }

  /** @return iterable */
  private function headers() {
    yield [['Cache-Control' => 'no-cache']];

    yield [function($uri, $file, $mime) {
      if (strstr($file->filename, 'fixture')) {
        yield 'Cache-Control' => 'no-cache';
      }
    }];

    yield [new class() {
      public function __invoke($uri, $file, $mime) {
        yield 'Cache-Control' => 'no-cache';
      }
    }];
  }

  /** @return iterable */
  private function types() {
    yield ['fixture.gif', 'image/gif'];
    yield ['fixture.png', 'image/png'];
    yield ['fixture.ico', 'image/x-icon'];
    yield ['fixture.svg', 'image/svg+xml; charset='.\xp::ENCODING];
    yield ['fixture.css', 'text/css; charset='.\xp::ENCODING];
    yield ['fixture.html', 'text/html; charset='.\xp::ENCODING];
    yield ['fixture.js', 'application/javascript; charset='.\xp::ENCODING];
    yield ['fixture.json', 'application/json; charset='.\xp::ENCODING];
    yield ['fixture.rss', 'application/rss+xml; charset='.\xp::ENCODING];
  }

  #[Test]
  public function can_create() {
    new AssetsFrom('.');
  }

  #[Test]
  public function can_create_with_folder() {
    new AssetsFrom(new Folder('.'));
  }

  #[Test]
  public function typical_ua_header_negotiated() {
    Assert::equals(
      ['br' => 1.04, 'gzip' => 1.02, 'deflate' => 1.01, '*' => 0.01],
      (new AssetsFrom('.'))->negotiate('gzip, deflate, br')
    );
  }

  #[Test]
  public function typical_ua_header_preferring_gzip() {
    Assert::equals(
      ['gzip' => 1.02, 'br' => 1.01, 'deflate' => 1.0, '*' => 0.01],
      (new AssetsFrom('.'))->preferring(['gzip', 'br'])->negotiate('gzip, deflate, br')
    );
  }

  #[Test]
  public function identity_accepted() {
    Assert::equals(
      ['identity' => 1.0, '*' => 0.01],
      (new AssetsFrom('.'))->negotiate('identity')
    );
  }

  #[Test]
  public function header_with_qvalues_accepted() {
    Assert::equals(
      ['deflate' => 1.01, 'gzip' => 1.0, '*' => 0.5],
      (new AssetsFrom('.'))->negotiate('deflate, gzip;q=1.0, *;q=0.5')
    );
  }

  #[Test, Values(from: 'types')]
  public function mime($file, $expected) {
    Assert::equals($expected, (new AssetsFrom('.'))->mime($file));
  }

  #[Test, Values([null, 'deflate', 'gzip, deflate'])]
  public function directly_serves_file($for) {
    $files= ['fixture.css' => self::CONTENTS];
    $res= $this->serve(new AssetsFrom($this->folderWith($files)), '/fixture.css', [
      'Accept-Encoding' => $for
    ]);

    Assert::equals(200, $res->status());
    Assert::equals('text/css; charset='.\xp::ENCODING, $res->headers()['Content-Type']);
    Assert::false(isset($res->headers()['Content-Encoding']));
    $this->assertFile($files['fixture.css'], $res);
  }

  #[Test]
  public function includes_vary_header() {
    $res= $this->serve(new AssetsFrom($this->folderWith(['fixture.css' => '...'])), '/fixture.css');
    Assert::equals('Accept-Encoding', $res->headers()['Vary']);
  }

  #[Test, Values([[['fixture.css' => self::CONTENTS]], [['fixture.css.gz' => self::COMPRESSED]]])]
  public function handles_conditional_requests($files) {
    $res= $this->serve(new AssetsFrom($this->folderWith($files)), '/fixture.css', [
      'Accept-Encoding'   => 'gzip, deflate, br',
      'If-Modified-Since' => gmdate('D, d M Y H:i:s T', time() + 86400)
    ]);

    Assert::equals(304, $res->status());
  }

  #[Test]
  public function returns_error_when_file_is_not_found() {
    $files= ['fixture.css' => self::CONTENTS];
    $res= $this->serve(new AssetsFrom($this->folderWith($files)), '/nonexistant.css');

    Assert::equals(404, $res->status());
  }

  #[Test]
  public function returns_error_when_folder_is_accessed() {
    $res= $this->serve(new AssetsFrom($this->folderWith([])), '/');

    Assert::equals(404, $res->status());
  }

  #[Test, Values([['fixture.css.gz', 'gzip'], ['fixture.css.br', 'br'], ['fixture.css.dfl', 'deflate'], ['fixture.css.bz2', 'bzip2']])]
  public function serves_compressed_when_file_present($file, $encoding) {
    $files= [$file => self::COMPRESSED];
    $res= $this->serve(new AssetsFrom($this->folderWith($files)), '/fixture.css', [
      'Accept-Encoding' => 'gzip, bzip2, deflate, br'
    ]);

    Assert::equals($encoding, $res->headers()['Content-Encoding']);
    $this->assertFile($files[$file], $res);
  }

  #[Test]
  public function prefers_gzip_compressed_when_gz_file_present() {
    $files= ['fixture.css' => self::CONTENTS, 'fixture.css.gz' => self::COMPRESSED];
    $res= $this->serve(new AssetsFrom($this->folderWith($files)), '/fixture.css', [
      'Accept-Encoding' => 'gzip, br'
    ]);

    Assert::equals('gzip', $res->headers()['Content-Encoding']);
    $this->assertFile($files['fixture.css.gz'], $res);
  }

  #[Test]
  public function prefers_brotli_compressed_when_gz_and_br_files_present() {
    $files= ['fixture.css' => self::CONTENTS, 'fixture.css.gz' => self::COMPRESSED, 'fixture.css.br' => self::COMPRESSED];
    $res= $this->serve(new AssetsFrom($this->folderWith($files)), '/fixture.css', [
      'Accept-Encoding' => 'gzip, br'
    ]);

    Assert::equals('br', $res->headers()['Content-Encoding']);
    $this->assertFile($files['fixture.css.br'], $res);
  }

  #[Test]
  public function prefers_uncompressed_for_identity() {
    $files= ['fixture.css' => self::CONTENTS, 'fixture.css.gz' => self::COMPRESSED];
    $res= $this->serve(new AssetsFrom($this->folderWith($files)), '/fixture.css', [
      'Accept-Encoding' => 'identity;q=1.0, gzip;q=0.9'
    ]);

    Assert::equals('identity', $res->headers()['Content-Encoding']);
    $this->assertFile($files['fixture.css'], $res);
  }

  #[Test, Values([null, 'deflate', 'br, deflate;q=0.5', 'test'])]
  public function prefers_uncompressed_without_browser_support($for) {
    $files= ['fixture.css' => self::CONTENTS, 'fixture.css.gz' => self::COMPRESSED];
    $res= $this->serve(new AssetsFrom($this->folderWith($files)), '/fixture.css', [
      'Accept-Encoding' => $for
    ]);

    Assert::false(isset($res->headers()['Content-Encoding']));
    $this->assertFile($files['fixture.css'], $res);
  }

  #[Test, Values([null, 'deflate', 'br, deflate;q=0.5', 'test'])]
  public function returns_error_without_browser_support($for) {
    $files= ['fixture.css.gz' => self::COMPRESSED];
    $res= $this->serve(new AssetsFrom($this->folderWith($files)), '/fixture.css', [
      'Accept-Encoding' => $for
    ]);

    Assert::equals(404, $res->status());
  }

  #[Test, Values(from: 'headers')]
  public function adding_headers($headers) {
    $files= ['fixture.css' => self::CONTENTS];
    $res= $this->serve(
      (new AssetsFrom($this->folderWith($files)))->with($headers),
      '/fixture.css'
    );

    Assert::equals('no-cache', $res->headers()['Cache-Control']);
  }

  #[Test, Values(from: 'headers')]
  public function headers_are_not_added_when_file_does_not_exist($headers) {
    $files= [];
    $res= $this->serve(
      (new AssetsFrom($this->folderWith($files)))->with($headers),
      '/fixture.css'
    );

    Assert::equals(404, $res->status());
    Assert::false(isset($res->headers()['Cache-Control']));
  }

  #[Test, Values(from: 'headers')]
  public function headers_are_not_added_for_conditional($headers) {
    $files= ['fixture.css' => self::CONTENTS];
    $res= $this->serve(
      (new AssetsFrom($this->folderWith($files)))->with($headers),
      '/fixture.css',
      ['If-Modified-Since' => gmdate('D, d M Y H:i:s T', time() + 86400)]
    );

    Assert::equals(304, $res->status());
    Assert::false(isset($res->headers()['Cache-Control']));
  }

  #[After]
  public function cleanup() {
    foreach ($this->remove as $folder) {
      $folder->unlink();
    }
  }
}