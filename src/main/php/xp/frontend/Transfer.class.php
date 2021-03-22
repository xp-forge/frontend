<?php namespace xp\frontend;

use io\File;
use io\streams\InputStream;

class Transfer implements InputStream {
  private $transferred= 0;

  public function __construct(private InputStream $in, private File $file, private $progress) { }

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
