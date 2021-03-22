<?php namespace xp\frontend;

use io\File;
use io\streams\InputStream;

class Transfer implements InputStream {
  private $transferred= 0;
  private $in, $file, $progress;

  public function __construct(InputStream $in, File $file, $progress) {
    $this->in= $in;
    $this->file= $file;
    $this->progress= $progress;
  }

  public function available() { return $this->in->available(); }

  public function close() {
    $this->progress['final']($this->transferred);
    $this->in->close();
  }

  public function read($limit= 8192) {
    $chunk= $this->in->read($limit);
    $this->transferred+= $this->file->write($chunk);
    $this->progress['update']($this->transferred);
    return $chunk;
  }
}
