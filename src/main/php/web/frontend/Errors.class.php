<?php namespace web\frontend;

interface Errors {

  /**
   * Handles errors
   *
   * @param  lang.Throwable $cause
   * @return web.frontend.Target
   * @throws web.Error
   */
  public function handle($cause);
}