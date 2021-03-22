<?php namespace xp\frontend;

class Dependency {
  public $library, $constraint, $files;

  public function __construct(string $library, string $constraint, array $files) {
    $this->library= $library;
    $this->constraint= $constraint;
    $this->files= $files;
  }
}