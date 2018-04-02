<?php namespace web\frontend;

use web\Handler;
use web\Error;
use lang\ElementNotFoundException;
use lang\XPClass;
use lang\reflect\TargetInvocationException;

class Frontend implements Handler {
  private $delegates= [];
  private $templates, $type;

  /**
   * Instantiates a new frontend
   *
   * @param  object $handler
   * @param  web.frontend.Templates $templates
   * @param  string $base
   */
  public function __construct($handler, Templates $templates, $base= '') {
    $this->type= cast(typeof($handler), XPClass::class);
    $this->templates= $templates;
    $this->base= rtrim($base, '/');

    $p= '';
    foreach ($this->type->getMethods() as $method) {
      $name= $method->getName();
      foreach ($method->getAnnotations() as $verb => $segment) {
        $p.= '|((*:'.$name.')^'.$verb.($segment ? preg_replace('/\{([^}]+)\}/', '(?<$1>[^/]+)', $segment) : '.+').'$)';
      }

      $this->delegates[$name]= new Delegate($handler, $method);
    }
    $this->pattern= '#'.substr($p, 1).'#';
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
    preg_match_all($this->pattern, $verb.$req->uri()->path(), $matches, PREG_SET_ORDER);
    if (empty($matches)) {
      throw new Error(400, 'Method '.$req->method().' not supported by '.$this->type->getName());
    }
    $delegate= $this->delegates[$matches[0]['MARK']];

    // Verify CSRF token for anything which is not a GET or HEAD request
    if (!in_array($verb, ['get', 'head']) && $req->value('token') !== $req->param('token')) {
      throw new Error(400, 'Missing CSRF token for '.$delegate->name());
    }

    try {
      $args= [];
      foreach ($delegate->parameters() as $name => $source) {
        if (isset($matches[0][$name])) {
          $args[]= $matches[0][$name];
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