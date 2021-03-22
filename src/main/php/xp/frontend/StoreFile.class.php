<?php namespace xp\frontend;

use util\URI;

class StoreFile {

  public function process(URI $base, $stream) {
    yield 'store' => [$base->path(), $stream];
  }
}