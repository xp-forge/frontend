<?php namespace web\frontend;

use web\Handler;
use web\Error;
use lang\ElementNotFoundException;
use lang\XPClass;
use lang\reflect\TargetInvocationException;

class Frontend implements Handler {
  private $delegates, $templates, $base;

  /**
   * Instantiates a new frontend
   *
   * @param  web.frontend.Delegates|object $arg
   * @param  web.frontend.Templates $templates
   * @param  string $base
   */
  public function __construct($arg, Templates $templates, $base= '') {
    $this->delegates= $arg instanceof Delegates ? $arg : new MethodsIn($arg);
    $this->templates= $templates;
    $this->base= rtrim($base, '/');
  }

  /**
   * Handles request
   *
   * @param  web.Request $req
   * @param  web.Response $res
   * @return var
   */
  public function handle($req, $res) {
    $res->header('Server', 'XP/Frontend');

    $verb= strtolower($req->method());
    if (null === ($target= $this->delegates->target($verb, $req->uri()->path()))) {
      throw new Error(400, 'Method '.$req->method().' not supported by any delegate');
    }
    list($delegate, $matches)= $target;

    // Verify CSRF token for anything which is not a GET or HEAD request
    if (!in_array($verb, ['get', 'head']) && $req->value('token') !== $req->param('token')) {
      throw new Error(400, 'Missing CSRF token for '.$delegate->name());
    }

    try {
      $args= [];
      foreach ($delegate->parameters() as $name => $source) {
        if (isset($matches[$name])) {
          $args[]= $matches[$name];
        } else {
          $args[]= $source($req, $name);
        }
      }

      $delegate->invoke($args, $this->templates)->transfer($req, $res, $this->base);
    } catch (TargetInvocationException $e) {
      throw new Error(500, $e->getCause());
    }
  }
}