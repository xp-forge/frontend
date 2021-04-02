<?php namespace xp\frontend;

use io\streams\InputStream;
use util\URI;

abstract class Response implements InputStream {
  public $origin;
  protected $in, $progress;

  /** Creates a new response */
  public function __construct(URI $origin, InputStream $in, $progress) {
    $this->origin= $origin;
    $this->in= $in;
    $this->progress= $progress;
  }

  /** @return bool */
  public abstract function cached();

  /** @return int */
  public function available() { return $this->in->available(); }

  /** @return void */
  public function close() { $this->in->close(); }

  /**
   * Reads from this response
   *
   * @param  int $limit
   * @return string
   */
  public function read($limit= 8192) { return $this->in->read($limit); }
}