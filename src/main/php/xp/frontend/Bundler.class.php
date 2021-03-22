<?php namespace xp\frontend;

use io\{File, Folder};
use util\cmd\Console;

class Bundler {
  private $fetch, $resolve, $target;

  public function __construct(Fetch $fetch, Resolver $resolve, Folder $target) {
    $this->fetch= $fetch;
    $this->resolve= $resolve;
    $this->target= $target;
  }

  public function create(string $name, Dependencies $dependencies) {
    Console::writeLine("\e[32mGenerating ", $name, " bundle\e[0m");

    $sources= [];
    foreach ($dependencies as $dependency) {
      Console::write("\e[37;1m", $dependency->library, "\e[0m@", $dependency->constraint, " => ");

      $version= $this->resolve->version($dependency->library, $dependency->constraint);

      Console::writeLine(" \e[37;1m", $version, "\e[0m");
    }
  }
}