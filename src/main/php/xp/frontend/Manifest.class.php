<?php namespace xp\frontend;

use io\File;
use text\json\{Json, FileInput, FileOutput};

class Manifest {
  private $file;
  private $in;
  private $out= [];

  /** @param string|io.Path|io.File $file */
  public function __construct($file) {
    $this->file= $file instanceof File ? $file : new File($file);
    $this->in= $this->file->exists() ? Json::read(new FileInput($this->file)) : [];
  }

  /** @return io.File */
  public function file() { return $this->file; }

  /**
   * Associate compound name with resolved name
   *
   * @param  string $compound
   * @param  string $resolved
   * @return string
   */
  public function associate($compound, $resolved) {
    $this->out[$compound]= $resolved;
    return $resolved;
  }

  /**
   * Returns all compound and resolved names removed during upgrading
   *
   * @return [:string] 
   */
  public function removed() {
    return array_diff($this->in, $this->out);
  }

  /**
   * Save this manifest
   *
   * @return void
   */
  public function save() {
    Json::write($this->out, new FileOutput($this->file));
  }
}