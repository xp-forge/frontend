<?php namespace xp\frontend;

use util\URI;

class RemoteDependency extends Dependency {
  private $base, $files;

  /**
   * Creates a new dependency
   *
   * @param  string|util.URI $base
   * @param  string[] $files
   */
  public function __construct($base, array $files) {
    $this->base= rtrim($base, '/');
    $this->files= $files;
  }

  public function files() {
    foreach ($this->files as $file) {
      yield $file => function($cdn) use($file) {
        return $cdn->fetch(new URI($this->base.'/'.$file));
      };
    }
  }
}