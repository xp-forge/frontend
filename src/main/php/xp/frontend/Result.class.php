<?php namespace xp\frontend;

use io\streams\StreamTransfer;
use io\{File, Folder};
use util\cmd\Console;

class Result {
  private $cdn, $target, $handlers;
  private $sources= [];

  public function __construct(CDN $cdn, Folder $target, array $handlers) {
    $this->cdn= $cdn;
    $this->target= $target;
    $this->handlers= $handlers;
  }

  public function fetch($uri, $revalidate= true, $path= null) {
    $path= $uri->path();
    $type= substr($path, strrpos($path, '.') + 1);

    Console::write("> \e[34m[", $type, "]: ", $path ?? (string)$uri, "\e[0m ");
    $stream= $this->cdn->fetch($uri, $revalidate);
    Console::writeLine();

    $handler= $this->handlers[$type] ?? $this->handlers['*'];
    $handler->process($this, $uri, $stream);
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

  /**
   * Creates bundles from source and returns them
   *
   * @param  string $name
   * @return iterable
   */
  public function bundles($name) {
    foreach ($this->sources as $type => $list) {
      $bundle= new File($this->target, $name.'.'.$type);
      $bundle->open(File::WRITE);
      foreach ($list as $bytes) {
        $bundle->write(implode('', $bytes));
      }
      $bundle->close();
      yield $bundle;
    }
  }
}