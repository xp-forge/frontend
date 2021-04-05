<?php namespace web\frontend;

use io\File;
use text\json\{Json, Input, FileInput};

/**
 * Assets manifest 
 *
 * @see   https://webpack.js.org/concepts/manifest/
 * @test  web.frontend.unittest.AssetsManifestTest
 */
class AssetsManifest {
  public $assets;

  /** @param text.json.Input|io.Path|io.File|string */
  public function __construct($arg) {
    $this->assets= Json::read($arg instanceof Input ? $arg : new FileInput($arg));
  }

  /**
   * Returns an immutable cache header if a file is contained in this
   * manifest.
   *
   * @param  io.Path|io.File|string $file
   * @return ?string
   */
  public function immutable($file) {
    $compare= $file instanceof File ? $file->filename : (string)$file;
    foreach ($this->assets as $resolved) {
      if (0 === strpos($compare, $resolved)) return 'max-age=31536000, immutable';
    }
    return null;
  }
}