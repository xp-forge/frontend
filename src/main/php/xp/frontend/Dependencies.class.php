<?php namespace xp\frontend;

class Dependencies implements \IteratorAggregate {
  private $spec;

  public function __construct(array $spec) {
    $this->spec= $spec;
  }

  /** @return iterable */
  public function getIterator() {
    foreach ($this->spec as $dependency => $files) {
      $p= strpos($dependency, '@');
      if (false === $p) {
        $library= $dependency;
        $constraint= null;
      } else {
        $library= substr($dependency, 0, $p);
        $constraint= substr($dependency, $p + 1);
      }

      yield new Dependency($library, $constraint, $files);
    }
  }
}