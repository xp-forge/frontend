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
   * @param  object $handler
   * @param  web.frontend.Templates $templates
   * @param  string $base
   */
  public function __construct($handler, Templates $templates, $base= '') {
    $this->delegates= new Delegates($handler);
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

      $result= $delegate->invoke($args);
      if ($result instanceof View) {
        $res->answer($result->status);
        foreach ($result->headers as $name => $value) {
          $res->header($name, $value);
        }
        $template= $result->template ?: $delegate->group();
        $context= $result->context;
      } else {
        $res->answer(200);
        $template= $delegate->group();
        $context= $result;
      }

      if (null === $context) {
        $res->flush();
      } else {
        $context['base']= $this->base;
        $context['request']= ['params' => $req->params(), 'values' => $req->values()];

        $res->header('Content-Type', 'text/html; charset=utf-8');
        $out= $res->stream();
        try {
          $this->templates->write($template, $context, $out);
        } finally {
          $out->close();
        }
      }
    } catch (TargetInvocationException $e) {
      throw new Error(500, $e->getCause());
    }
  }
}