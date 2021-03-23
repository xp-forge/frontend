<?php namespace xp\frontend;

use io\{File, Folder};
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

    // Download all dependencies
    foreach ($dependencies as $dependency) {
      Console::write("\e[37;1m", $dependency->library, "\e[0m@", $dependency->constraint, " => ");
      $version= $this->resolve->version($dependency->library, $dependency->constraint);
      Console::writeLine("\e[37;1m", $version, "\e[0m");

      foreach ($dependency->files as $file) {
        $result->fetch($this->cdn->locate($dependency->library, $version, $file));
      }
    }

    // Create bundles from compiled sources
    foreach ($result->sources as $type => $list) {
      $bundle= new File($target, $name.'.'.$type);
      $bundle->open(File::WRITE);
      foreach ($list as $bytes) {
        $bundle->write(implode('', $bytes));
      }
      $bundle->close();
      yield $bundle;
    }
  }
}