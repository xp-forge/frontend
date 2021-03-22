<?php namespace xp\frontend;

use io\File;
use io\streams\InputStream;

class Download implements InputStream {
  private $transferred= 0;
  private $in, $progress;

  public function __construct(InputStream $in, $progress) {
    $this->in= $in;
    $this->progress= $progress;
  }

  public function available() { return $this->in->available(); }

  public function close() {
    $this->progress['final']($this->transferred);
    $this->in->close();
  }

  public function read($limit= 8192) {
    $chunk= $this->in->read($limit);
    $this->transferred+= strlen($chunk);
    $this->progress['update']($this->transferred);
    return $chunk;
  }
}
