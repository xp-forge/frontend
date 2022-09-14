<?php namespace xp\frontend;

use io\streams\InputStream;

/** Stores assets and dependencies by with fingerprints in their filenames */
class WithFingerprints extends Files {
  const HASH = 'sha1';
  private $manifest;

  /**
   * Creates a new files implementation
   *
   * @param string|io.Folder $target
   * @param xp.frontend.Manifest $manifest
   */
  public function __construct($target, $manifest) {
    parent::__construct($target);
    $this->manifest= $manifest;
  }

  private function hashed($name, $type, $hash) {
    return $name.'.'.substr($hash, 0, 7).'.'.$type;
  }

  public function resolve($name, $type, $hash) {
    return $this->manifest->associate(
      $name.'.'.$type,
      $this->hashed($name, $type, $hash)
    );
  }

  /**
   * Store a given input stream under a given name and return file its
   * contents were written to.
   *
   * @throws io.IOException
   */
  public function store(InputStream $in, string $path): Bundle {
    $type= substr($path, strrpos($path, '.') + 1);

    // Store file temporarily and calculate a checksum while writing to it
    $out= new Bundle(tempnam($this->target->getURI(), self::class), $type);
    try {
      $ctx= hash_init(self::HASH);
      while ($in->available()) {
        $chunk= $in->read();
        hash_update($ctx, $chunk);
        $out->write($chunk);
      }
      $hash= hash_final($ctx);
    } finally {
      $in->close();
      $out->close();
    }

    // Register in manifest
    $name= basename($path, '.'.$type);
    $this->resolve($name, $type, $hash);

    // Rename the files to [filename].[contenthash].[extension]
    foreach ($out->files() as $suffix => $file) {
      $file->move($this->target->getURI().$this->hashed($name, $type, $hash).$suffix);
    }
    return $out;
  }
}