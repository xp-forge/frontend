<?php namespace xp\frontend;

use util\URI;

class StoreFile {

  public function process(Result $result, URI $base, $stream) {
    $result->store($base->path(), $stream);
  }
}