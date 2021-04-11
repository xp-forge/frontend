<?php namespace xp\frontend;

use io\Path;

class LocalDependency extends Dependency {
  private $folder, $files;

  /**
   * Creates a new dependency
   *
   * @param  string|io.Path $folder
   * @param  string[] $files
   */
  public function __construct($folder, array $files) {
    $this->folder= $folder;
    $this->files= $files;
  }

  public function files() {
    foreach ($this->files as $file) {
      yield $file => function() use($file) {
        return new LocalFile(new Path($this->folder, $file));
      };
    }
  }
}