<?php namespace web\frontend;

use lang\Reflection;

/** Creates routing based on a given instance */
class MethodsIn extends Delegates {

  /** Creates with a given object */
  public function __construct(object $instance) {
    $type= Reflection::type($instance);
    if ($handler= $type->annotation(Handler::class)) {
      $this->with($instance, (string)$handler->argument(0));
    } else {
      $this->with($instance, '/');
    }
    uksort($this->patterns, fn($a, $b) => strlen($b) - strlen($a));
  }
}