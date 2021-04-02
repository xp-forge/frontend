<?php namespace xp\frontend;

use io\streams\InputStream;
use util\URI;

class Cached extends Response {

  /** Creates a new cached response */
  public function __construct(URI $origin, InputStream $in, bool $validated, $progress) {
    parent::__construct($origin, $in, $progress);
    $this->progress['cached']($validated);
  }

  /** @return bool */
  public function cached() { return true; }
}
