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
    foreach ($dependency->files() as $file => $fetch) {
      $stream= $fetch($this->cdn);

      $handler= $this->handlers[substr($file, strrpos($file, '.') + 1)] ?? $this->handlers['*'];
      $handler->process($this, $stream, $stream->origin);
    }
  }

  public function fetch($uri, $revalidate= true) {
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