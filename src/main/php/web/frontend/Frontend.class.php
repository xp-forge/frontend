<?php namespace web\frontend;

use lang\reflect\TargetInvocationException;
use web\{Error, Handler};

/**
 * Annotation-based frontend
 *
 * @test  web.frontend.unittest.FrontendTest
 * @test  web.frontend.unittest.HandlingTest
 * @test  web.frontend.unittest.CSRFTokenTest
 */
class Frontend implements Handler {
  private $delegates, $templates, $errors;
  public $globals;

  /**
   * Instantiates a new frontend
   *
   * @param  web.frontend.Delegates|object $arg
   * @param  web.frontend.Templates $templates
   * @param  [:var] $globals
   * @param  ?web.frontend.Errors $handling
   */
  public function __construct($arg, Templates $templates, $globals= [], Errors $handling= null) {
    $this->delegates= $arg instanceof Delegates ? $arg : new MethodsIn($arg);
    $this->templates= $templates;
    $this->globals= is_string($globals) ? ['base' => rtrim($globals, '/')] : $globals;
    $this->errors= $handling;
  }

  /** Overwrites error handler */
  public function handling(Errors $errors): self {
    $this->errors= $errors;
    return $this;
  }

  /** Returns error handler */
  public function errors(): Errors {
    return $this->errors ?? $this->errors= new RaiseErrors();
  }

  /**
   * Determines view to be displayed, handling errors while going along.
   *
   * @param  web.Request $req
   * @param  web.Response $res
   * @return web.frontend.View
   */
  private function view($req, $res) {
    static $CSRF_EXEMPT= ['get' => true, 'head' => true];

    $method= strtolower($req->method());
    if (null === ($target= $this->delegates->target($method, $req->uri()->path()))) {
      return $this->errors()->handle(new Error(404, 'Cannot route '.$req->method().' requests to '.$req->uri()->path()));
    }
    list($delegate, $matches)= $target;

    // Verify CSRF token for anything which is not a GET or HEAD request
    if (!isset($CSRF_EXEMPT[$method]) && $req->value('token') !== $req->param('token')) {
      return $this->errors()->handle(new Error(403, 'Incorrect CSRF token for '.$delegate->name()));
    }

    try {
      $args= [];
      foreach ($delegate->parameters() as $name => $source) {
        $args[]= $matches[$name] ?? $source($req, $name);
      }

      return $delegate->invoke($args);
    } catch (TargetInvocationException $e) {
      return $this->errors()->handle($e->getCause());
    }
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
    $this->view($req, $res)->using($this->templates)->transfer($req, $res, $this->globals);
  }
}