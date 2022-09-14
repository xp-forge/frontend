<?php namespace xp\frontend;

use io\File;
use io\streams\{OutputStream, GzCompressingOutputStream};

class Bundle implements OutputStream {
  const COMPRESS = ['css', 'js', 'svg', 'json', 'xml', 'ttf', 'otf', 'woff', 'woff2', 'eot'];

  private static $zlib, $brotli;
  private $files= [];
  private $output= [];

  static function __static() {
    self::$zlib= extension_loaded('zlib');
    self::$brotli= extension_loaded('brotli');
  }

  /**
   * Creates a new bundle
   *
   * @param  io.Path|string $path
   * @param  ?string $type
   */
  public function __construct($path, $type= null) {
    $this->output[]= $this->output($path);

    // Check whether it's typically worthwhile compressing a file based on the
    // given type (falling back to the file extension if omitted).
    if (in_array($type ?? strtolower(substr($path, strrpos($path, '.') + 1)), self::COMPRESS)) {
      self::$zlib && $this->output[]= new GzCompressingOutputStream($this->output($path, '.gz'), 9);
      self::$brotli && $this->output[]= new BrCompressingOutputStream($this->output($path, '.br'), 11);
    }
  }

  /**
   * Registers a given file and returns its output stream
   *
   * @param  io.Path|string $path
   * @param  string $suffix
   * @param  io.OutputStream
   */
  private function output($path, $suffix= '') {
    $this->files[$suffix]= $file= new File($path.$suffix);
    return $file->out();
  }

  /**
   * Returns the files produced by this bundle
   *
   * @return [:io.File]
   */
  public function files() { return $this->files; }

  /**
   * Write a string
   *
   * @param  var $arg
   * @return void
   */
  public function write($bytes) {
    foreach ($this->output as $stream) {
      $stream->write($bytes);
    }
  }

  /**
   * Flush this bundle
   *
   * @return void
   */
  public function flush() {
    foreach ($this->output as $stream) {
      $stream->flush();
    }
  }

  /**
   * Close this bundle
   *
   * @return void
   */
  public function close() {
    foreach ($this->output as $stream) {
      $stream->close();
    }
  }
}