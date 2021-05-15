<?php namespace web\frontend;

use lang\reflect\TargetInvocationException;
use web\{Error, Handler};

class Frontend implements Handler {
  private $delegates, $templates;
  public $globals;

  /**
   * Instantiates a new frontend
   *
   * @param  web.frontend.Delegates|object $arg
   * @param  web.frontend.Templates $templates
   * @param  [:var] $globals
   */
  public function __construct($arg, Templates $templates, $globals= []) {
    $this->delegates= $arg instanceof Delegates ? $arg : new MethodsIn($arg);
    $this->templates= $templates;
    $this->globals= is_string($globals) ? ['base' => rtrim($globals, '/')] : $globals;
  }

  /**
   * Handles request
   *
   * @param  web.Request $req
   * @param  web.Response $res
   * @return var
   * @throws web.Error
   */
  public function handle($req, $res) {
    $res->header('Server', 'XP/Frontend');

    $method= strtolower($req->method());
    if (null === ($target= $this->delegates->target($method, $req->uri()->path()))) {
      throw new Error(404, 'Cannot route '.$req->method().' requests to '.$req->uri()->path());
    }
    list($delegate, $matches)= $target;

    // Verify CSRF token for anything which is not a GET or HEAD request
    if (!in_array($method, ['get', 'head']) && $req->value('token') !== $req->param('token')) {
      throw new Error(400, 'Missing CSRF token for '.$delegate->name());
    }

    try {
      $args= [];
      foreach ($delegate->parameters() as $name => $source) {
        $args[]= $matches[$name] ?? $source($req, $name);
      }

      $delegate->invoke($args, $this->templates)->transfer($req, $res, $this->globals);
    } catch (TargetInvocationException $e) {
      $cause= $e->getCause();
      if ($cause instanceof Error) {
        throw $cause;
      } else {
        throw new Error(500, $cause->getMessage(), $cause);
      }
    }
  }
}