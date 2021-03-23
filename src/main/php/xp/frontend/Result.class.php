<?php namespace xp\frontend;

use io\streams\StreamTransfer;
use io\{File, Folder};
use util\cmd\Console;

class Result {
  public $sources= [];
  private $cdn, $target, $handlers;

  public function __construct(CDN $cdn, Folder $target, array $handlers) {
    $this->cdn= $cdn;
    $this->target= $target;
    $this->handlers= $handlers;
  }

  public function fetch($uri, $revalidate= true, $path= null) {
    $path= $uri->path();
    $type= substr($path, strrpos($path, '.') + 1);
    $handler= $this->handlers[$type] ?? $this->handlers['*'];

    Console::write("> \e[34m[", $type, "]: ", $path ?? (string)$uri, "\e[0m ");
    $stream= $this->cdn->fetch($uri, $revalidate);
    $handler->process($this, $uri, $stream);
    Console::writeLine();
  }

  public function store($path, $stream) {
    $t= new File($this->target, $path);
    $f= new Folder($t->getPath());
    $f->exists() || $f->create();

    with (new StreamTransfer($stream, $t->out()), function($self) {
      $self->transferAll();
    });
  }

  public function prefix($type, $bytes) {
    $this->sources[$type][0][]= $bytes;
  }

  public function concat($type, $bytes) {
    $this->sources[$type][1][]= $bytes;
  }
}