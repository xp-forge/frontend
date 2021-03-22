<?php namespace xp\frontend;

use io\streams\InputStream;

class Cached implements InputStream {
  private $in, $progress;

  public function __construct(InputStream $in, bool $validated, $progress) {
    $this->in= $in;
    $this->progress= $progress;
    $this->progress['cached']($validated);
  }

  public function available() { return $this->in->available(); }

  public function close() {
    $this->in->close();
  }

  public function read($limit= 8192) {
    return $this->in->read($limit);
  }
}
