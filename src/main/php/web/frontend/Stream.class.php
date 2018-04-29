<?php namespace web\frontend;

use io\File;
use io\Path;
use io\streams\InputStream;
use io\streams\MemoryInputStream;

class Stream implements Result {
  public $in;
  public $type;
  public $size;
  public $status= 200;
  public $headers= [];

  private function __construct($in, $type, $size= null) {
    $this->in= $in;
    $this->type= $type;
    $this->size= $size;
  }

  /**
   * Sets stream and content type
   *
   * @param  io.streams.InputStream|io.File|io.Path|util.Bytes|string $arg
   * @param  string $type
   * @return self
   */
  public static function of($arg, $type) {
    if ($arg instanceof InputStream) {
      return new self($arg, $type);
    } else if ($arg instanceof File) {
      return new self($arg->in(), $type, $arg->size());
    } else if ($arg instanceof Path) {
      $f= $arg->asFile();
      return new self($f, $type, $f->size());
    } else {
      $s= (string)$arg;
      return new self(new MemoryInputStream($s), $type, strlen($s));
    }
  }

  /**
   * Sets status
   *
   * @param  int $status
   * @return self
   */
  public function status($status) {
    $this->status= $status;
    return $this;
  }

  /**
   * Adds a header
   *
   * @param  string $name
   * @param  string $value
   * @return self
   */
  public function header($name, $value) {
    $this->headers[$name]= $value;
    return $this;
  }

  /**
   * Sets size in bytes, if known
   *
   * @param  int $size
   * @return self
   */
  public function size($size) {
    $this->size= $size;
    return $this;
  }

  /**
   * Transfers this result
   *
   * @param  web.Request $req
   * @param  web.Response $res
   * @param  string $base
   * @return void
   */
  public function transfer($req, $res, $base) {
    $res->answer($this->status);
    foreach ($this->headers as $name => $value) {
      $res->header($name, $value);
    }

    $res->transfer($this->in, $this->type, $this->size);
  }
}