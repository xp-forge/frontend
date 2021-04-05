<?php namespace xp\frontend;

use util\cmd\Console;

class Result {
  const HASH = 'sha1';

  private $cdn, $handlers;
  private $sources= [];
  private $hashes= [];

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
      $uri= $this->cdn->locate($dependency->library, $dependency->version, $file);
      $path= $uri->path();
      $handler= $this->handlers[substr($path, strrpos($path, '.') + 1)] ?? $this->handlers['*'];
      $handler->process($this, $this->fetch($uri), $uri);
    }
  }

  public function fetch($uri, $revalidate= true, $location= null) {
    Console::writef("\r\e[0K> \e[34m%s\e[0m ", $location ? '…/'.$location : $uri);
    return $this->cdn->fetch($uri, $revalidate);
  }

  public function prefix($type, $bytes) {
    $this->sources[$type][0][]= $bytes;
    hash_update($this->hashes[$type] ?? $this->hashes[$type]= hash_init(self::HASH), $bytes);
  }

  public function concat($type, $bytes) {
    $this->sources[$type][1][]= $bytes;
    hash_update($this->hashes[$type] ?? $this->hashes[$type]= hash_init(self::HASH), $bytes);
  }

  /**
   * Returns sources
   *
   * @return iterable
   */
  public function sources() {
    foreach ($this->sources as $type => $list) {
      yield $type => new Source($list, hash_final($this->hashes[$type]));
    }
  }
}