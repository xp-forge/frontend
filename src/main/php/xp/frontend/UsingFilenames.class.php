<?php namespace xp\frontend;

use io\File;
use io\streams\InputStream;

/** Stores assets and dependencies by their original filenames */
class UsingFilenames extends Files {

  public function resolve($name, $type, $hash) {
    return $name.'.'.$type;
  }

  /**
   * Store a given input stream under a given name and return file its
   * contents were written to.
   *
   * @throws io.IOException
   */
  public function store(InputStream $in, string $path): File {
    $out= new File($this->target, basename($path));
    $out->open(File::WRITE);
    try {
      while ($in->available()) {
        $out->write($in->read());
      }
      return $out;
    } finally {
      $in->close();
      $out->close();
    }
  }
}