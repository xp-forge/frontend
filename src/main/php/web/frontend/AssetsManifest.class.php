<?php namespace web\frontend;

use io\File;
use text\json\{Json, Input, FileInput};
use util\URI;

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
   * @param  io.Path|io.File|util.URI|string $file
   * @return ?string
   */
  public function immutable($path) {
    if ($path instanceof URI) {
      $compare= basename($path->path());
    } else if ($path instanceof File) {
      $compare= $path->filename;
    } else {
      $compare= (string)$path;
    }

    return in_array($compare, $this->assets) ? 'max-age=31536000, immutable' : null;
  }
}