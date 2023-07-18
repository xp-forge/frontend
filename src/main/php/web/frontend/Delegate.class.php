<?php namespace web\frontend;

use lang\reflection\Method;
use lang\{IllegalArgumentException, Reflection};
use web\frontend\View;

class Delegate {
  private static $SOURCES;
  private $instance, $method;
  private $parameters= null;

  static function __static() {
    self::$SOURCES= [
      'value'   => function($req, $name) { return $req->value($name); },
      'cookie'  => function($req, $name) { return $req->cookie($name); },
      'header'  => function($req, $name) { return $req->header($name); },
      'param'   => function($req, $name) { return $req->param($name); },
      'default' => function($req, $name) { return $req->stream(); },
      'request' => function($req, $name) { return $req; },
      'segment' => function($req, $name) { throw new IllegalArgumentException('Missing segment "'.$name.'"'); },
    ];
  }

  /**
   * Creates a new delegate
   *
   * @param  object $instance
   * @param  string|lang.reflection.Method $method
   */
  public function __construct($instance, $method) {
    $this->instance= $instance;
    $this->method= $method instanceof Method ? $method : Reflection::type($instance)->method($method);
  }

  /** @return string */
  public function group() {
    $t= strtolower(get_class($this->instance));
    return false === ($p= strrpos($t, '\\')) ? $t : substr($t, $p + 1);
  }

  /** @return string */
  public function name() {
    return nameof($this->instance).'::'.$this->method->name();
  }

  /**
   * Returns a map of named sources to read arguments from request. Lazily
   * initialized on first use.
   *
   * @return [:(function(web.Request, string): var)]
   */
  public function parameters() {
    if (null === $this->parameters) {
      $this->parameters= [];
      foreach ($this->method->parameters() as $param) {

        // Check for parameter annotations...
        foreach ($param->annotations() as $annotation) {
          $source= self::$SOURCES[$annotation->name()] ?? self::$SOURCES['default'];
          $name= $annotation->argument(0) ?? $param->name();

          if ($param->optional()) {
            $this->parameters[$name]= function($req, $name) use($source, $param) {
              return $source($req, $name) ?? $param->default();
            };
          } else {
            $this->parameters[$name]= $source;
          }
          continue 2;
        }

        // ...falling back to selecting the parameter from the segments
        $this->parameters[$param->name()]= self::$SOURCES['segment'];
      }
    }

    return $this->parameters;
  }

  /**
   * Invokes this delegate
   *
   * @param  var[] $args
   * @return web.frontend.View
   * @throws lang.reflect.TargetInvocationException
   */
  public function invoke($args) {
    $result= $this->method->invoke($this->instance, $args);
    if ($result instanceof View) {
      $result->template ?? $result->template= $this->group();
      return $result;
    } else {
      return View::named($this->group())->with((array)$result);
    }
  }
}