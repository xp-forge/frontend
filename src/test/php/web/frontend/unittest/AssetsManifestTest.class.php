<?php namespace web\frontend\unittest;

use io\File;
use lang\FormatException;
use test\{Assert, Expect, Test, Values};
use text\json\StringInput;
use util\URI;
use web\frontend\AssetsManifest;

class AssetsManifestTest {

  /** @return iterable */
  private function inputs() {
    yield ['{}'];
    yield ['{"vendor.css" : "vendor.f6cad2a.css"}'];
    yield ['{"vendor.css" : "vendor.f6cad2a.css", "vendor.js" : "vendor.7b1f7cb.js"}'];
  }

  /**
   * Creates a new fixture
   *
   * @param  string $input
   * @return web.frontend.AssetsManifest
   */
  private function fixture($input) {
    return new AssetsManifest(new StringInput($input));
  }

  #[Test]
  public function can_create() {
    $this->fixture('{}');
  }

  #[Test, Expect(class: FormatException::class, message: '/Unexpected token/')]
  public function cannot_create_with_malformed() {
    $this->fixture('not.json');
  }

  #[Test, Values(from: 'inputs')]
  public function assets($input) {
    Assert::equals(json_decode($input, true), (array)$this->fixture($input)->assets);
  }

  #[Test]
  public function immutable_asset() {
    Assert::equals(
      'max-age=31536000, immutable',
      $this->fixture('{"vendor.css" : "vendor.f6cad2a.css"}')->immutable('vendor.f6cad2a.css')
    );
  }

  #[Test]
  public function immutable_file() {
    Assert::equals(
      'max-age=31536000, immutable',
      $this->fixture('{"vendor.css" : "vendor.f6cad2a.css"}')->immutable(new File('vendor.f6cad2a.css'))
    );
  }

  #[Test]
  public function immutable_uri() {
    Assert::equals(
      'max-age=31536000, immutable',
      $this->fixture('{"vendor.css" : "vendor.f6cad2a.css"}')->immutable(new URI('/assets/vendor.f6cad2a.css'))
    );
  }

  #[Test]
  public function regular_asset() {
    Assert::null(
      $this->fixture('{"vendor.css" : "vendor.f6cad2a.css"}')->immutable('style.css')
    );
  }

  #[Test]
  public function regular_gzipped_asset() {
    Assert::null(
      $this->fixture('{"vendor.css" : "vendor.f6cad2a.css"}')->immutable('vendor.f6cad2a.css.gz')
    );
  }
}