<?php namespace web\frontend;

/** @test web.frontend.unittest.ViewTest */
class View {
  public $template;
  public $fragment= null;
  public $templates;
  public $status= 200;
  public $context= [];
  public $headers= ['Cache-Control' => 'no-cache'];
  public $stream= true;

  /** @param string $template */
  private function __construct($template) {
    $this->template= $template;
  }

  /**
   * Sets template to use
   *
   * @param  string $name
   * @return self
   */
  public static function named($name) {
    sscanf($name, "%[^#]#%[^\r]", $template, $fragment);
    $self= new self($template);
    $self->fragment= $fragment;
    $self->headers['Content-Type']= 'text/html; charset='.\xp::ENCODING;
    return $self;
  }

  /**
   * Returns an empty view
   *
   * @return self
   */
  public static function empty() {
    $self= new self(null);
    $self->headers['Content-Length']= 0;
    $self->stream= false;
    return $self;
  }

  /**
   * Redirects to another URL
   *
   * @param  string|util.URI $uri
   * @param  int $status Defaults to 302
   * @return self
   */
  public static function redirect($url, $status= 302) {
    $self= new self(null);
    $self->status= $status;
    $self->headers['Location']= $url;
    $self->headers['Content-Length']= 0;
    $self->stream= false;
    return $self;
  }

  /**
   * Dispatches request internally
   *
   * @param  string|util.URI $uri
   * @param  [:string] $params
   * @return web.frontend.Dispatch
   */
  public static function dispatch($url, $params= []) {
    return new Dispatch($url, $params);
  }

  /**
   * Creates an error view with a given status and template. The template
   * will be named *errors/{template}* or *errors/{status}* if its name
   * is omitted.
   *
   * @param  int $status
   * @param  ?string $template
   * @return self
   */
  public static function error(int $status, $template= null) {
    $self= new self('errors/'.($template ?? $status));
    $self->status= $status;
    $self->headers['Content-Type']= 'text/html; charset='.\xp::ENCODING;
    return $self;
  }

  /**
   * Sets status
   *
   * @param  int $status
   * @return self
   */
  public function status($status) {
    $this->status= $status;
    return $this;
  }

  /**
   * Sets fragment
   *
   * @param  string $fragment
   * @return self
   */
  public function fragment($fragment) {
    $this->fragment= $fragment;
    return $this;
  }

  /**
   * Adds a header
   *
   * @param  string $name
   * @param  string $value
   * @return self
   */
  public function header($name, $value) {
    $this->headers[$name]= $value;
    return $this;
  }

  /**
   * Gives context
   *
   * @param  [:var] $context
   * @return self
   */
  public function with(array $context) {
    $this->context+= $context;
    return $this;
  }

  /**
   * Sets templates
   *
   * @param  web.frontend.Templates $templates
   * @return self
   */
  public function using($templates) {
    $this->templates= $templates;
    return $this;
  }

  /**
   * Sets `Cache-Control` to header, which defaults to "no-cache"
   *
   * @see    https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control
   * @param  string $control Header value
   * @return self
   */
  public function cache($control) {
    $this->headers['Cache-Control']= $control;
    return $this;
  }

  /**
   * Transfers this result
   *
   * @param  web.Request $req
   * @param  web.Response $res
   * @param  [:var] $globals
   * @return var
   */
  public function transfer($req, $res, $globals) {
    $res->answer($this->status);
    foreach ($this->headers as $name => $value) {
      $res->header($name, $value);
    }

    if ($this->stream && $out= $res->stream()) {
      try {
        $this->templates->write(
          $this->template,
          ['request' => $req] + $this->context + $globals,
          $out,
          $this->fragment
        );
      } finally {
        $out->close();
      }
    } else {
      $res->flush();
    }
  }
}