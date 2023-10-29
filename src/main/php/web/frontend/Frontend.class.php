<?php namespace web\frontend;

use lang\reflection\TargetException;
use web\{Error, Handler, Request};

/**
 * Annotation-based frontend
 *
 * @test  web.frontend.unittest.FrontendTest
 * @test  web.frontend.unittest.HandlingTest
 * @test  web.frontend.unittest.CSRFTokenTest
 */
class Frontend implements Handler {
  private $delegates, $templates;
  private $errors= null;
  private $security= null;
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

  /** Overwrites error handler */
  public function handling(Errors $errors): self {
    $this->errors= $errors;
    return $this;
  }

  /** Overwrites security */
  public function enacting(Security $security): self {
    $this->security= $security;
    return $this;
  }

  /** Returns delegates */
  public function delegates(): Delegates { return $this->delegates; }

  /** Returns templating */
  public function templates(): Templates { return $this->templates; }

  /** Returns error handler */
  public function errors(): Errors { return $this->errors ?? $this->errors= new RaiseErrors(); }

  /** Returns security */
  public function security(): Security { return $this->security ?? $this->security= new Security(); }

  /**
   * Selects a target for a given method and path
   *
   * @param  util.URI|web.Request|string $arg
   * @return ?var[]
   */
  public function target($method, $arg= '/') {
    if ($arg instanceof URI) {
      $path= $arg->path();
    } else if ($arg instanceof Request) {
      $path= $arg->uri()->path();
    } else {
      $path= (string)$arg;
    }
    return $this->delegates->target($method, $path);
  }

  /**
   * Determines view to be displayed, handling errors while going along.
   *
   * @param  web.Request $req
   * @param  web.Response $res
   * @param  web.frontend.Delegate $delegate
   * @param  [:var] $matches
   * @return web.frontend.View
   */
  private function view($req, $res, $delegate, $matches= []) {
    static $CSRF_EXEMPT= ['get' => true, 'head' => true];

    if (null === $delegate) {
      return $this->errors()->handle(new Error(404, 'Cannot route '.$req->method().' requests to '.$req->uri()->path()));
    }

    // Verify CSRF token for anything which is not a GET or HEAD request
    $token= $req->param('token') ?? $req->header('X-Csrf-Token');
    if (!isset($CSRF_EXEMPT[strtolower($req->method())]) && $req->value('token') !== $token) {
      return $this->errors()->handle(new Error(403, 'Incorrect CSRF token for '.$delegate->name()));
    }

    try {
      $args= [];
      foreach ($delegate->parameters() as $name => $source) {
        $args[]= $matches[$name] ?? $source($req, $name);
      }

      return $delegate->invoke($args);
    } catch (TargetException $e) {
      return $this->errors()->handle($e->getCause());
    }
  }

  /**
   * Handles request
   *
   * @param  web.Request $req
   * @param  web.Response $res
   * @return ?var[] $target
   * @throws web.Error
   */
  public function handle($req, $res, $target= null) {
    static $NOT_FOUND= [null];

    // Handle HEAD requests with GET unless explicitely specified
    $method= strtolower($req->param('_method') ?? $req->method());
    $path= $req->uri()->path();
    if ('head' === $method) {
      $target= $this->delegates->target($method, $path) ?? $this->delegates->target('get', $path) ?? $NOT_FOUND;
      $view= $this->view($req, $res, ...$target);
      $view->stream= false;
    } else {
      $target= $target ?? $this->delegates->target($method, $path) ?? $NOT_FOUND;
      $view= $this->view($req, $res, ...$target);
    }

    $res->header('Server', 'XP/Frontend');
    $this->security()->apply($view)->using($this->templates)->transfer($req, $res, $this->globals);
  }
}