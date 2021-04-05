<?php namespace xp\frontend;

use io\streams\InputStream;
use util\URI;

class StoreFile {
  private $files;

  /** @param xp.frontend.Files */
  public function __construct($files) {
    $this->files= $files;
  }

  public function process(Result $result, InputStream $stream, URI $uri= null) {
    $this->files->store($stream, $uri->path());
  }
}