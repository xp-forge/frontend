<?php namespace xp\frontend;

use io\streams\{StreamTransfer, InputStream};
use io\{File, Folder};
use util\URI;

class StoreFile {
  private $target;

  /** @param string|io.Folder $target */
  public function __construct($target) {
    $this->target= $target instanceof Folder ? $target : new Folder($target);
  }

  public function process(Result $result, InputStream $stream, URI $uri= null) {
    $t= new File($this->target, $uri->path());
    $f= new Folder($t->getPath());
    $f->exists() || $f->create();

    with (new StreamTransfer($stream, $t->out()), function($self) {
      $self->transferAll();
    });
  }
}