<?php namespace xp\frontend;

use io\Path;
use util\URI;

class LocalFile extends Response {

  /** Creates a new local response */
  public function __construct(Path $path) {
    parent::__construct(URI::file($path), $path->asFile()->in(), null);
  }

  /** @return bool */
  public function cached() { return true; }
}
