<?php namespace xp\frontend;

use io\streams\OutputStream;
use lang\Closeable;

class Source implements Closeable {
  private $list;

  /** Creates a new source */
  public function __construct(array $list) {
    $this->list= $list;
  }

  /** Transfers this source to an output stream */
  public function transfer(OutputStream $out): self {
    foreach ($this->list as $bytes) {
      foreach ($bytes as $chunk) {
        $out->write($chunk);
      }
    }
    return $this;
  }

  /** @return void */
  public function close() { }
}