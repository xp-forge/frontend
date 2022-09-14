<?php namespace xp\frontend;

use io\streams\InputStream;

class Source implements InputStream {
  private $list;
  public $hash;

  /** Creates a new source */
  public function __construct(array $list, string $hash) {
    $this->list= $list;
    $this->hash= $hash;
  }

  /** @return int */
  public function available() {
    return sizeof($this->list);
  }

  /** @return string */
  public function read($bytes= 8192) {
    return implode('', array_pop($this->list));
  }

  /** @return void */
  public function close() { }
}