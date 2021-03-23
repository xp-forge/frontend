<?php namespace xp\frontend;

/**
 * Resolves dependencies' version in the specified bundle against the
 * `dependencies` key in package.json.
 *
 * @see  https://docs.npmjs.com/cli/v6/configuring-npm/package-json
 */
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