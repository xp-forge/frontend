<?php namespace web\frontend;

class View {
  public $template;
  public $status= 200;
  public $context= [];
  public $headers= [];

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
   * Gives context
   *
   * @param  [:var] $context
   * @return self
   */
  public function with($context) {
    $this->context= $context;
    return $this;
  }
}