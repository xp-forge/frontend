<?php namespace xp\frontend;

use io\File;
use io\streams\{OutputStream, GzCompressingOutputStream};

class Bundle implements OutputStream {
  private static $zlib;
  private $files= [];
  private $output= [];

  static function __static() {
    self::$zlib= extension_loaded('zlib');
  }

  /**
   * Creates a new bundle
   *
   * @param  io.Folder|string $path
   * @param  string $name
   */
  public function __construct($path, $name) {
    $this->output[]= $this->output(new File($path, $name));
    if (self::$zlib) {
      $this->output[]= new GzCompressingOutputStream($this->output(new File($path, $name.'.gz')), 9);
    }
  }

  /**
   * Registers a given file and returns its output stream
   *
   * @param  io.File $file
   * @param  io.OutputStream
   */
  private function output($file) {
    $this->files[]= $file;
    return $file->out();
  }

  /**
   * Returns the files produced by this bundle
   *
   * @return io.File[]
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