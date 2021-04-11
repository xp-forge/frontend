<?php namespace xp\frontend;

class LibraryDependency extends Dependency {
  public $library, $version, $files;

  /**
   * Creates a new dependency
   *
   * @param  string $library
   * @param  string $version
   * @param  string[] $files
   */
  public function __construct(string $library, $version, array $files) {
    $this->library= $library;
    $this->version= $version;
    $this->files= $files;
  }

  public function files() {
    foreach ($this->files as $file) {
      yield $file => function($cdn) use($file) {
        return $cdn->fetch($cdn->locate($this->library, $this->version, $file));
      };
    }
  }
}