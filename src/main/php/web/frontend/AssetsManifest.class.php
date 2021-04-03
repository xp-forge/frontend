<?php namespace web\frontend;

use text\json\{Json, Input, FileInput};

class AssetsManifest {
  public $assets;

  /** @param text.json.Input|io.Path|io.File|string */
  public function __construct($arg) {
    $this->assets= Json::read($arg instanceof Input ? $arg : new FileInput($arg));
  }
}