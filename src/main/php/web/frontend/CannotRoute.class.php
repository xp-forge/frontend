<?php namespace web\frontend;

use web\Error;

class CannotRoute extends Error {
  public $method, $path;

  /**
   * Creates a new *cannot route* error
   *
   * @param  string $method
   * @param  string $path
   * @param  lang.Throwable $cause
   */
  public function __construct($method, $path, $cause= null) {
    parent::__construct(404, "Cannot route {$method} requests to {$path}", $cause);
    $this->method= $method;
    $this->path= $path;
  }
}