<?php namespace xp\frontend;

use io\streams\InputStream;

class Source implements InputStream {
  private $list;

  /** Creates a new source */
  public function __construct(array $list) {
    $this->list= $list;
  }

  /** @return int */
  public function available() { return sizeof($this->list); }

  /** @return void */
  public function close() { }

  /**
   * Reads from this transfer
   *
   * @param  int $limit
   * @return string
   */
  public function read($limit= 8192) {
    if ($this->list) {
      $bytes= array_shift($this->list);
      return implode('', $bytes);
    }
    return null;
  }
}