<?php namespace web\frontend;

use lang\{IllegalArgumentException, Reflection};

/** Creates routing based on a given instance */
class MethodsIn extends Delegates {

  /** @param object $instance */
  public function __construct($instance) {
    if (!is_object($instance)) {
      throw new IllegalArgumentException('Expected an object, have '.typeof($instance));
    }

    $type= Reflection::type($instance);
    if ($handler= $type->annotation(Handler::class)) {
      $this->with($instance, (string)$handler->argument(0));
    } else {
      $this->with($instance, '/');
    }
    uksort($this->patterns, function($a, $b) { return strlen($b) - strlen($a); });
  }
}