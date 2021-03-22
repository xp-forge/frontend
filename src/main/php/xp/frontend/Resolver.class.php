<?php namespace xp\frontend;

use lang\IllegalArgumentException;
use text\json\{Json, StreamInput};

class Resolver {
  private $fetch, $registry;

  public function __construct(Fetch $fetch, string $registry= 'https://registry.npmjs.org') {
    $this->fetch= $fetch;
    $this->registry= rtrim($registry, '/').'/';
  }

  public function version(string $library, string $constraint): string {
    $info= Json::read(new StreamInput($this->fetch->get($this->registry.$library)));

    // TBI: Create constraints
    if ('^' === $constraint[0]) {
      $constraint= substr($constraint, 1);
    }

    // Find newest version
    krsort($info['versions']);
    foreach ($info['versions'] as $id => $_) {
      if (strstr($id, 'beta')) continue;
      if (0 === strncmp($constraint, $id, strlen($constraint))) return $id;
    }

    throw new IllegalArgumentException(sprintf(
      'Unmatched version constraint %s, have [%s]',
      $constraint,
      implode(', ', array_keys($info['versions']))
    ));
  }
}