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

  /** @return iterable */
  public function files() {
    foreach ($this->files as $file) {
      $path= new Path($this->folder, $file);

      // Glob patterns, directories or a file
      if (strcspn($file, '*?[]{}') < strlen($file)) {
        $it= array_map(function($f) { return new Path($f); }, glob($path, GLOB_MARK | GLOB_NOSORT | GLOB_BRACE));
      } else if ($path->isFolder()) {
        $it= $path->asFolder()->entries();
      } else {
        $it= [$path];
      }

      foreach ($it as $entry) {
        yield (string)$entry => function() use($entry) {
          return new LocalFile($entry);
        };
      }
    }
  }
}