<?php namespace web\frontend;

use lang\{IllegalArgumentException, Reflection};

/** Matches request and routes to correct delegate */
class Delegates {
  public $patterns= [];

  /**
   * Routes to instance methods based on annotations
   *
   * @param  object $instance
   * @param  string $base
   * @return self
   * @throws lang.IllegalArgumentException
   */
  public function with($instance, $base= '/') {
    if (!is_object($instance)) {
      throw new IllegalArgumentException('Expected an object, have '.typeof($instance));
    }

    $base= rtrim($base, '/');
    foreach (Reflection::type($instance)->methods() as $method) {
      $name= $method->name();
      foreach ($method->annotations() as $annotation) {
        $segment= $annotation->argument(0);
        $pattern= preg_replace(
          ['/\{([^:}]+):([^}]+)\}/', '/\{([^}]+)\}/'],
          ['(?<$1>$2)', '(?<$1>[^/]+)'],
          $base.('/' === $segment || null === $segment ? '/?' : $segment)
        );
        $this->patterns['#'.$annotation->name().$pattern.'$#']= new Delegate($instance, $method);
      }
    }
    return $this;
  }

  /**
   * Returns target delegate and matches for a given HTTP verb and path, or
   * NULL if not such target exists
   *
   * @param  string $verb
   * @param  string $path
   * @return ?var[]
   */
  public function target($verb, $path) {
    $match= $verb.$path;
    foreach ($this->patterns as $pattern => $delegate) {
      if (preg_match($pattern, $match, $matches)) return [$delegate, $matches];
    }
    return null;
  }
}