<?php namespace xp\frontend;

use io\streams\StreamTransfer;
use io\{File, Folder};
use util\URI;
use util\cmd\Console;

class Bundler {
  private $fetch, $resolve, $handlers, $target;

  public function __construct(Fetch $fetch, Resolver $resolve, $handlers, Folder $target) {
    $this->fetch= $fetch;
    $this->resolve= $resolve;
    $this->handlers= $handlers;
    $this->target= $target;
  }

  public function create(string $name, Dependencies $dependencies): iterable {
    $sources= [];
    $operations= [
      'fetch' => function($uri, $revalidate= true) use(&$sources, &$operations) {
        $path= $uri->path();
        $type= substr($path, strrpos($path, '.') + 1);

        Console::write("> \e[34m[", $type, "]: ", (string)$uri, "\e[0m ");
        $stream= $this->fetch->get($uri, $revalidate);

        $handler= $this->handlers[$type] ?? $this->handlers['*']; 
        foreach ($handler->process($uri, $stream) as $operation => $arguments) {
          $operations[$operation](...$arguments);
        }
        Console::writeLine();
      },
      'store'  => function($path, $stream) {
        $t= new File($this->target, $path);
        $f= new Folder($t->getPath());
        $f->exists() || $f->create();

        with (new StreamTransfer($stream, $t->out()), function($self) {
          $self->transferAll();
        });
      },
      'prefix' => function($type, $bytes) use(&$sources) {
        $sources[$type][0][]= $bytes;
      },
      'concat' => function($type, $bytes) use(&$sources) {
        $sources[$type][1][]= $bytes;
      },
    ];

    foreach ($dependencies as $dependency) {
      Console::write("\e[37;1m", $dependency->library, "\e[0m@", $dependency->constraint, " => ");
      $version= $this->resolve->version($dependency->library, $dependency->constraint);
      Console::writeLine(" \e[37;1m", $version, "\e[0m");

      foreach ($dependency->files as $file) {
        $operations['fetch'](new URI(sprintf('https://cdn.jsdelivr.net/npm/%s@%s/%s', $dependency->library, $version, $file)));
      }
    }

    foreach ($sources as $type => $list) {
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