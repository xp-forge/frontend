<?php namespace web\frontend\unittest\bundler;

use io\streams\MemoryOutputStream;
use lang\IllegalArgumentException;
use test\verify\Runtime;
use test\{Assert, Expect, Test, Values};
use xp\frontend\BrCompressingOutputStream;

#[Runtime(extensions: ['brotli'])]
class BrCompressingOutputStreamTest {

  #[Test]
  public function can_create() {
    new BrCompressingOutputStream(new MemoryOutputStream());
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function using_invalid_compression_level() {
    new BrCompressingOutputStream(new MemoryOutputStream(), -1);
  }

  #[Test, Values([1, 6, 11])]
  public function write($level) {
    $out= new MemoryOutputStream();

    $fixture= new BrCompressingOutputStream($out, $level);
    $fixture->write('Hello');
    $fixture->write(' ');
    $fixture->write('World');
    $fixture->close();

    Assert::equals('Hello World', brotli_uncompress($out->bytes()));
  }
}