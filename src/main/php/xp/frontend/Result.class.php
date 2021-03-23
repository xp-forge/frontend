<?php namespace xp\frontend;

use util\cmd\Console;

class Result {
  private $cdn, $handlers;
  private $sources= [];

  public function __construct(CDN $cdn, array $handlers) {
    $this->cdn= $cdn;
    $this->handlers= $handlers;
  }

  /**
   * Include a given dependency with a given version in this result
   *
   * @return void
   */
  public function include(Dependency $dependency, string $version) {
    foreach ($dependency->files as $file) {
      $this->fetch($this->cdn->locate($dependency->library, $version, $file));
    }
  }

  public function fetch($uri, $revalidate= true, $name= null) {
    $path= $uri->path();
    $type= substr($path, strrpos($path, '.') + 1);

    Console::write("> \e[34m[", $type, "]: ", $name ?? (string)$uri, "\e[0m ");
    $stream= $this->cdn->fetch($uri, $revalidate);
    Console::writeLine();

    $handler= $this->handlers[$type] ?? $this->handlers['*'];
    $handler->process($this, $uri, $stream);
  }

  public function prefix($type, $bytes) {
    $this->sources[$type][0][]= $bytes;
  }

  public function concat($type, $bytes) {
    $this->sources[$type][1][]= $bytes;
  }

  /**
   * Creates bundles from source and returns them
   *
   * @return iterable
   */
  public function bundles() {
    foreach ($this->sources as $type => $list) {
      yield $type => new Source($list);
    }
  }
}