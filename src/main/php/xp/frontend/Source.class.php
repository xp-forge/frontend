<?php namespace xp\frontend;

use io\streams\OutputStream;
use lang\Closeable;

class Source implements Closeable {
  private $list;
  public $hash;

  /** Creates a new source */
  public function __construct(array $list, string $hash) {
    $this->list= $list;
    $this->hash= $hash;
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