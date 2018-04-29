<?php namespace web\frontend;

use lang\IllegalArgumentException;

/**
 * Matches request and routes to correct delegate
 */
class Delegates {
  private $patterns= [];

  /** @param object $instance */
  public function __construct($instance) {
    if (!is_object($instance)) {
      throw new IllegalArgumentException('Expected an object, have '.typeof($instance));
    }

    foreach (typeof($instance)->getMethods() as $method) {
      $name= $method->getName();
      foreach ($method->getAnnotations() as $verb => $segment) {
        $p= '#'.$verb.($segment ? preg_replace('/\{([^}]+)\}/', '(?<$1>[^/]+)', $segment) : '.+').'$#';
        $this->patterns[$p]= new Delegate($instance, $method);
      }
    }
  }

  /**
   * Returns target for a given HTTP verb and path
   *
   * @param  string $verb
   * @param  string $path
   * @return web.frontend.Delegate or NULL
   */
  public function target($verb, $path) {
    $match= $verb.$path;
    foreach ($this->patterns as $pattern => $delegate) {
      if (preg_match($pattern, $match, $matches)) return [$delegate, $matches];
    }
    return null;
  }
}