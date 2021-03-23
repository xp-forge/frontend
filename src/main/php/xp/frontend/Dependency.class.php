<?php namespace xp\frontend;

class Dependency {
  public $library, $constraint, $files;
  public $version= null;

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

  /** Resolves this dependency's version */
  public function resolve(string $version): self {
    $this->version= $version;
    return $this;
  }
}