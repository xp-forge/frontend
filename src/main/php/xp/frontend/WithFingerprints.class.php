<?php namespace xp\frontend;

use io\File;
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

  public function resolve($name, $type, $hash) {
    return $this->manifest->associate(
      $name.'.'.$type,
      $name.'.'.substr($hash, 0, 7).'.'.$type
    );
  }

  /**
   * Store a given input stream under a given name and return file its
   * contents were written to.
   *
   * @throws io.IOException
   */
  public function store(InputStream $in, string $path): File {

    // Store file temporarily and calculate a checksum while writing to it
    $out= new File(tempnam($this->target->getURI(), self::class));
    $out->open(File::WRITE);
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

    // Rename the file to [filename].[contenthash].[extension]
    $file= basename($path);
    $type= substr($file, strrpos($file, '.') + 1);
    $out->move($this->target->getURI().$this->resolve(basename($file, '.'.$type), $type, $hash));
    return $out;
  }
}