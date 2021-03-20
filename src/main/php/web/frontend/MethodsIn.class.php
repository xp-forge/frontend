<?php namespace web\frontend;

use lang\IllegalArgumentException;

/**
 * Creates routing based on a given instance
 */
class MethodsIn extends Delegates {

  /** @param object $instance */
  public function __construct($instance) {
    $type= typeof($instance);
    if (!is_object($instance)) {
      throw new IllegalArgumentException('Expected an object, have '.$type);
    }

    $this->with($instance, $type->hasAnnotation('handler') ? (string)$type->getAnnotation('handler') : '/');
    uksort($this->patterns, function($a, $b) { return strlen($b) - strlen($a); });
  }
}