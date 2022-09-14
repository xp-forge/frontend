<?php namespace xp\frontend;

use io\Path;
use io\streams\InputStream;

/** Stores assets and dependencies by their original filenames */
class UsingFilenames extends Files {

  /**
   * Store a given input stream under a given name and return file its
   * contents were written to.
   *
   * @throws io.IOException
   */
  public function store(InputStream $in, string $path): Bundle {
    $out= new Bundle(new Path($this->target, basename($path)));
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