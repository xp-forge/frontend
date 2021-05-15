<?php namespace web\frontend;

use lang\IllegalArgumentException;
use web\Error;

/**
 * Maps exceptions to views
 *
 * ```php
 * $handler= new Exceptions();
 *
 * // Use errors/{status} for web.Error instances, errors/500 for others
 * $handler->mapping(Throwable::class);
 *
 * // Always use errors/503 for all SQLExceptions
 * $handler->mapping(SQLException::class, 503);
 *
 * // Supply a handler function returning a view
 * $handler->mapping(InvalidOrder::class, fn($e) => View::error(404, 'invalid-order'));
 * ```
 *
 * @test  web.frontend.unittest.ExceptionsTest
 */
class Exceptions implements Errors {
  private $mapping= [];

  /**
   * Maps an exception type to a handler
   *
   * @param  string $type
   * @param  ?int|callable $handler
   * @return self
   */
  public function mapping($type, $handler= null) {
    if (null === $handler) {
      $this->mapping[$type]= function($cause) { return View::error($cause instanceof Error ? $cause->status() : 500); };
    } else if (is_int($handler)) {
      $this->mapping[$type]= function() use($handler) { return View::error($handler); };
    } else if (is_callable($handler)) {
      $this->mapping[$type]= $handler;
    } else {
      throw new IllegalArgumentException('Expected NULL, an integer or a callable');
    }
    return $this;
  }

  /**
   * Handles errors
   *
   * @param  lang.Throwable $cause
   * @return web.frontend.View
   * @throws web.Error
   */
  public function handle($cause) {
    foreach ($this->mapping as $type => $mapping) {
      if ($cause instanceof $type && ($view= $mapping($cause))) return $view->with(['cause' => $cause]);
    }

    // Unhanded, raise error and display default error page
    throw $cause instanceof Error ? $cause : new Error(500, $cause->getMessage(), $cause);
  }
}