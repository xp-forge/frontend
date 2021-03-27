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
  public function include(Dependency $dependency) {
    foreach ($dependency->files as $file) {
      $this->fetch($this->cdn->locate($dependency->library, $dependency->version, $file));
    }
  }

  public function fetch($uri, $revalidate= true, $location= null) {
    $path= $uri->path();
    $type= substr($path, strrpos($path, '.') + 1);

    Console::writef("\r\e[0K> \e[34m%s\e[0m ", $location ? '.../'.$location : $uri);
    $stream= $this->cdn->fetch($uri, $revalidate);

    $handler= $this->handlers[$type] ?? $this->handlers['*'];
    $handler->process($this, $stream, $location);
  }

  public function prefix($type, $bytes) {
    $this->sources[$type][0][]= $bytes;
  }

  public function concat($type, $bytes) {
    $this->sources[$type][1][]= $bytes;
  }

  /**
   * Returns sources
   *
   * @return iterable
   */
  public function sources() {
    foreach ($this->sources as $type => $list) {
      yield $type => new Source($list);
    }
  }
}