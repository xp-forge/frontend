<?php namespace web\frontend;

/**
 * Creates routing based on a given instance
 */
class MethodsIn extends Delegates {

  /** @param object $instance */
  public function __construct($instance) {
    $this->with($instance);
  }
}