<?php namespace xp\frontend;

class Dependency {
  public $library, $constraint, $files;

  /**
   * Creates a new dependency
   *
   * @param  string $library
   * @param  ?string $constraint
   * @param  string[] $files
   */
  public function __construct(string $library, $constraint, array $files) {
    $this->library= $library;
    $this->constraint= $constraint;
    $this->files= $files;
  }
}