<?php namespace xp\frontend;

use io\streams\InputStream;

class Cached extends Response {

  /** Creates a new cached response */
  public function __construct(InputStream $in, bool $validated, $progress) {
    parent::__construct($in, $progress);
    $this->progress['cached']($validated);
  }

  /** @return bool */
  public function cached() { return true; }
}
