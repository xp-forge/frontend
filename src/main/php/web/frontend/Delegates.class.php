<?php namespace web\frontend;

use lang\IllegalArgumentException;
use util\Objects;

class Delegates {
  private $map= [];
  private $pattern;

  /** @param object $instance */
  public function __construct($instance) {
    if (!is_object($instance)) {
      throw new IllegalArgumentException('Expected an object, have '.typeof($instance));
    }

    // Uses `(*MARK:NAME)` PCRE syntax to return names
    // See https://www.pcre.org/current/doc/html/pcre2syntax.html#SEC23
    $p= '';
    foreach (typeof($instance)->getMethods() as $method) {
      $name= $method->getName();
      foreach ($method->getAnnotations() as $verb => $segment) {
        $p.= '|((*:'.$name.')^'.$verb.($segment ? preg_replace('/\{([^}]+)\}/', '(?<$1>[^/]+)', $segment) : '.+').'$)';
      }

      $this->map[$name]= new Delegate($instance, $method);
    }
    $this->pattern= '#'.substr($p, 1).'#';
  }

  /**
   * Returns target for a given HTTP verb and path
   *
   * @param  string $verb
   * @param  string $path
   * @return web.frontend.Delegate or NULL
   */
  public function target($verb, $path) {
    preg_match_all($this->pattern, $verb.$path, $matches, PREG_SET_ORDER);
    return $matches ? [$this->map[$matches[0]['MARK']], $matches[0]] : null;
  }
}