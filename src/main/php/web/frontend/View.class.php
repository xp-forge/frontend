<?php namespace web\frontend;

class View {
  public $template;
  public $templates;
  public $status= 200;
  public $context= [];
  public $headers= ['Cache-Control' => 'no-cache'];

  /** @param string $template */
  private function __construct($template) {
    $this->template= $template;
  }

  /**
   * Sets template to use.
   *
   * @param  string $template
   * @return self
   */
  public static function named($template) {
    return new self($template);
  }

  /**
   * Redirects to another URL
   *
   * @param  string|util.URI $uri
   * @return self
   */
  public static function redirect($url) {
    $self= new self(null);
    $self->status= 302;
    $self->headers['Location']= $url;
    $self->context= null;
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
  public function with($context) {
    $this->context= $context;
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
   * @param  string $base
   * @return void
   */
  public function transfer($req, $res, $base) {
    $res->answer($this->status);
    foreach ($this->headers as $name => $value) {
      $res->header($name, $value);
    }

    if (null === $this->context) {
      $res->header('Content-Length', 0);
      $res->flush();
    } else {
      $this->context['base']= $base;
      $this->context['request']= $req;

      // See https://webhint.io/docs/user-guide/hints/hint-x-content-type-options/
      $res->header('Content-Type', 'text/html; charset=utf-8');
      $res->header('X-Content-Type-Options', 'nosniff');
      $out= $res->stream();
      try {
        $this->templates->write($this->template, $this->context, $out);
      } finally {
        $out->close();
      }
    }
  }
}