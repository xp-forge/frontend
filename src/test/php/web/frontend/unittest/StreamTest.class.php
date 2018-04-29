<?php namespace web\frontend\unittest;

use io\File;
use io\FileUtil;
use io\Path;
use io\streams\MemoryInputStream;
use lang\Environment;
use unittest\TestCase;
use util\Bytes;
use web\frontend\Stream;

class StreamTest extends TestCase {
  private $cleanup= [];

  /**
   * Returns a file with a given content
   *
   * @param  string $name
   * @param  string $content
   * @return io.File
   */
  private function fileWithContent($name, $contents) {
    $f= new File(Environment::tempDir(), uniqid(microtime(true)).'-'.$name);
    FileUtil::setContents($f, 'test');
    $this->cleanup[]= $f;
    return $f;
  }

  /** @return void */
  public function setUp() {
    $this->cleanup= [];
  }

  /** @return void */
  public function tearDown() {
    foreach ($this->cleanup as $file) {
      $file->isOpen() && $file->close();
      $file->unlink();
    }
  }

  #[@test]
  public function with_file() {
    $s= Stream::of($this->fileWithContent('test.txt', 'test'), 'text/plain');
    $this->assertEquals([4, 'text/plain'], [$s->size, $s->type]);
  }

  #[@test]
  public function with_path() {
    $s= Stream::of(new Path($this->fileWithContent('test.txt', 'test')->getURI()), 'text/plain');
    $this->assertEquals([4, 'text/plain'], [$s->size, $s->type]);
  }

  #[@test]
  public function with_stream() {
    $s= Stream::of(new MemoryInputStream('test'), 'text/plain');
    $this->assertEquals([null, 'text/plain'], [$s->size, $s->type]);
  }

  #[@test]
  public function with_bytes() {
    $s= Stream::of(new Bytes('test'), 'text/plain');
    $this->assertEquals([4, 'text/plain'], [$s->size, $s->type]);
  }

  #[@test]
  public function with_string() {
    $s= Stream::of('test', 'text/plain');
    $this->assertEquals([4, 'text/plain'], [$s->size, $s->type]);
  }

  #[@test, @values([
  #  ['test.txt', 'text/plain'],
  #  ['test.xml', 'text/xml'],
  #  ['test.gif', 'image/gif'],
  #])]
  public function mimetype_determined_by_file_extension($name, $type) {
    $s= Stream::of($this->fileWithContent($name, 'test'));
    $this->assertEquals([4, $type], [$s->size, $s->type]);
  }

  #[@test]
  public function passing_size() {
    $s= Stream::of(new MemoryInputStream('test'), 'text/plain')->size(4);
    $this->assertEquals([4, 'text/plain'], [$s->size, $s->type]);
  }

  #[@test]
  public function force_download() {
    $headers= [
      'Content-Disposition'       => 'attachment; filename="test.txt"',
      'Content-Transfer-Encoding' => 'binary'
    ];

    $s= Stream::of(new MemoryInputStream('test'))->download('test.txt');
    $this->assertEquals([null, 'application/octet-stream', $headers], [$s->size, $s->type, $s->headers]);
  }
}