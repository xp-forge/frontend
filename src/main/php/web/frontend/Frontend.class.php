<?php namespace web\frontend;

use web\Handler;
use web\Error;
use lang\ElementNotFoundException;
use lang\XPClass;
use lang\reflect\TargetInvocationException;

class Frontend implements Handler {
  private $handler, $templates, $type;

  /**
   * Instantiates a new frontend
   *
   * @param  object $handler
   * @param  web.frontend.Templates $templates
   * @param  string $base
   */
  public function __construct($handler, Templates $templates, $base= '') {
    $this->type= cast(typeof($handler), XPClass::class);
    $this->handler= $handler;
    $this->templates= $templates;
    $this->base= rtrim($base, '/');
  }

  /**
   * Returns the correct handler or NULL if not supported
   *
   * @param  string $verb
   * @param  util.URI $uri
   * @return var
   */
  private function handler($verb, $uri) {

    // Check methods annotated, e.g. @post
    foreach ($this->type->getMethods() as $method) {
      if (!$method->hasAnnotation($verb)) continue;

      $segment= $method->getAnnotation($verb);
      if (null === $segment) return [$method, []];

      // Check whether URI matches
      $pattern= '#^'.preg_replace('/\{([^}]+)\}/', '(?<$1>[^/]+)', $segment).'$#';
      if (preg_match($pattern, $uri->path(), $matches)) return [$method, $matches];
    }

    return null;
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
    if (null === ($handler= $this->handler($verb, $req->uri()))) {
      throw new Error(400, 'Method '.$verb.' not supported by '.$this->type->getName());
    }

    // Verify CSRF token for anything which is not a GET or HEAD request
    if (!in_array($verb, ['get', 'head']) && $req->value('token') !== $req->param('token')) {
      throw new Error(400, 'Missing CSRF token for '.$handler[0]->getName());
    }

    try {
      $args= [];
      foreach ($handler[0]->getParameters() as $param) {
        $name= $param->getName();
        if (isset($handler[1][$name])) {
          $args[]= $handler[1][$name];
          continue;
        }

        $annotations= $param->getAnnotations();
        if (array_key_exists('value', $annotations)) {
          $value= $req->value($annotations['value'] ?: $name);
        } else if (array_key_exists('cookie', $annotations)) {
          $value= $req->cookie($annotations['cookie'] ?: $name);
        } else if (array_key_exists('header', $annotations)) {
          $value= $req->header($annotations['header'] ?: $name);
        } else if (array_key_exists('param', $annotations)) {
          $value= $req->param($annotations['param'] ?: $name);
        } else {
          $args[]= $req->stream();
          continue;
        }

        if (null === $value) {
          $args[]= $param->isOptional() ? $param->getDefaultValue() : null;
        } else {
          $args[]= $value;
        }
      }

      $result= $handler[0]->invoke($this->handler, $args);
      if ($result instanceof View) {
        $res->answer($result->status);
        foreach ($result->headers as $name => $value) {
          $res->header($name, $value);
        }
        $template= $result->template ?: strtolower($this->type->getSimpleName());
        $context= $result->context;
      } else {
        $res->answer(200);
        $template= strtolower($this->type->getSimpleName());
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