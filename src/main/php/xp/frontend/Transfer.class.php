<?php namespace xp\frontend;

use io\streams\{InputStream, OutputStream};

class Transfer implements InputStream {
  private $in, $out;

  /**
   * Transfer returns everything read from the given input stream while
   * simultaneously writing to the given output stream.
   */
  public function __construct(InputStream $in, OutputStream $out) {
    $this->in= $in;
    $this->out= $out;
  }

  /** @return int */
  public function available() { return $this->in->available(); }

  /** @return void */
  public function close() {
    $this->in->close();
    $this->out->close();
  }

  /**
   * Reads from this transfer
   *
   * @param  int $limit
   * @return string
   */
  public function read($limit= 8192) {
    $chunk= $this->in->read($limit);
    $this->out->write($chunk);
    return $chunk;
  }
}