<?php namespace xp\frontend;

use io\streams\InputStream;
use io\{File, Folder};

abstract class Files {
  protected $target;

  /** @param string|io.Folder $target */
  public function __construct($target) {
    $this->target= $target instanceof Folder ? $target : new Folder($target);
    $this->target->exists() || $this->target->create();
  }

  public abstract function resolve($name, $type, $hash);

  /**
   * Store a given input stream under a given name and return file its
   * contents were written to.
   *
   * @throws io.IOException
   */
  public abstract function store(InputStream $in, string $path): File;

}