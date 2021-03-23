<?php namespace xp\frontend;

use lang\IllegalArgumentException;
use text\json\{Json, StreamInput};

/**
 * Resolves versions against meta information from NPM registry. Supports
 * the following constraint notations:
 *
 * - `1.3.2`: exactly 1.3.2
 * - `1.3.*`: >=1.3.0 <1.4.0
 * - `~1.3.2`: >=1.3.2 <1.4.0
 * - `~1.3`: >=1.3.0 <2.0.0
 * - `^1.3.2`: >=1.3.2 <2.0.0
 *
 * @see  https://getcomposer.org/doc/articles/versions.md
 * @test web.frontend.unittest.bundler.ResolverTest
 */
class Resolver {
  private $fetch, $registry;

  /** Creates a new resolver */
  public function __construct(Fetch $fetch, string $registry= 'https://registry.npmjs.org') {
    $this->fetch= $fetch;
    $this->registry= rtrim($registry, '/').'/';
  }

  /**
   * Selects all candidates between given lower and upper bounds.
   *
   * @param  [:var] $versions
   * @param  string $lo
   * @param  string $hi
   * @return [:var]
   */
  private function select($versions, $lo, $hi) {
    $compare= function($id) use($lo, $hi) {
      return
        3 === sscanf($id, "%*d.%*d.%*d%[^\r]", $extra) &&
        version_compare($id, $lo, 'ge') &&
        version_compare($id, $hi, 'lt')
      ;
    };
    return array_filter($versions, $compare, ARRAY_FILTER_USE_KEY);
  }

  /**
   * Resolves a given library name and version constraint, returning the
   * matching version number. Ignores all versions with extra after semantic
   * version number, e.g. `2.8.8-dev` or `1.2.3-beta4`.
   */
  public function version(string $library, string $constraint= null): string {
    $info= Json::read(new StreamInput($this->fetch->get($this->registry.$library)));

    if (null === $constraint) { // No constraint, simply find newest version
      $candidates= array_filter(
        $info['versions'],
        function($id) { return 3 === sscanf($id, "%*d.%*d.%*d%[^\r]", $extra); },
        ARRAY_FILTER_USE_KEY
      );
    } else if ('^' === $constraint[0]) { // Don't allow breaking changes
      $c= sscanf($constraint, '^%d.%d.%d');
      $candidates= $this->select(
        $info['versions'],
        vsprintf('%d.%d.%d', $c),
        0 === $c[0] ? sprintf('0.%d.0', $c[1] + 1) : sprintf('%d.0.0', $c[0] + 1)
      );
    } else if ('~' === $constraint[0]) { // Allow last digit specified to go up
      $c= sscanf($constraint, '~%d.%d.%d');
      $candidates= $this->select(
        $info['versions'],
        vsprintf('%d.%d.%d', $c),
        null === $c[2] ? sprintf('%d.0.0', $c[0] + 1) : sprintf('%d.%d.0', $c[0], $c[1] + 1)
      );
    } else if ('*' === $constraint[strlen($constraint) - 1]) { // Wilcard
      $c= sscanf($constraint, '%d.%d.%d');
      $candidates= $this->select(
        $info['versions'],
        vsprintf('%d.%d.%d', $c),
        null === $c[1] ? sprintf('%d.0.0', $c[0] + 1) : sprintf('%d.%d.0', $c[0], $c[1] + 1)
      );
    } else { // Direct version
      if (isset($info['versions'][$constraint])) return $constraint;
      $candidates= [];
    }

    // Find newest applicable version
    if ($candidates) {
      uksort($candidates, function($a, $b) { return version_compare($b, $a); });
      return key($candidates);
    }

    throw new IllegalArgumentException(sprintf(
      'Unmatched version constraint %s, have [%s]',
      $constraint,
      implode(', ', array_keys($info['versions']))
    ));
  }
}