<?php namespace web\frontend;

use web\Error;

/**
 * Raises errors and causes builtin web error pages to be rendered.
 *
 * @see  https://github.com/xp-forge/web/issues/53
 */
class RaiseErrors implements Errors {

  /**
   * Handles errors
   *
   * @param  lang.Throwable $cause
   * @return web.frontend.Target
   * @throws web.Error
   */
  public function handle($cause) {
    if ($cause instanceof Error) {
      throw $cause;
    } else {
      throw new Error(500, $cause->getMessage(), $cause);
    }
  }
}