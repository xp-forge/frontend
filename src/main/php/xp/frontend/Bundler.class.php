<?php namespace xp\frontend;

use io\Folder;
use util\cmd\Console;

class Bundler {
  private $cdn, $resolve, $handlers;

  /**
   * Creates a new bundler. By default, all dependencies are simply downloaded
   * and stored in the target directory passed to `create()`.
   */
  public function __construct(CDN $cdn, Resolver $resolve, array $handlers= []) {
    $this->cdn= $cdn;
    $this->resolve= $resolve;
    $this->handlers= $handlers + ['*' => new StoreFile()];
  }

  /**
   * Creates a bundle with the given name, downloading and processing dependencies
   * into a target folder. Returns created bundles, if any.
   */
  public function create(string $name, Dependencies $dependencies, Folder $target): iterable {
    $result= new Result($this->cdn, $target, $this->handlers);
    foreach ($dependencies as $dependency) {
      Console::write("\e[37;1m", $dependency->library, "\e[0m@", $dependency->constraint, " => ");
      $version= $this->resolve->version($dependency->library, $dependency->constraint);
      Console::writeLine("\e[37;1m", $version, "\e[0m");

      $result->include($dependency, $version);
    }
    return $result->bundles($name);
  }
}