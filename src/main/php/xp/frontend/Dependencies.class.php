<?php namespace xp\frontend;

class Dependencies implements \IteratorAggregate {
  const LATEST = ['', '*', 'latest'];

  private $spec, $dependencies;

  /** Creates new dependencies */
  public function __construct(array $spec, array $dependencies) {
    $this->spec= $spec;
    $this->dependencies= $dependencies;
  }

  /** @return iterable */
  public function getIterator() {
    foreach ($this->spec as $library => $files) {
      $constraint= $this->dependencies[$library];
      yield new Dependency(
        $library,
        in_array($constraint, self::LATEST) ? null : $constraint,
        array_map('trim', explode('|', $files))
      );
    }
  }
}