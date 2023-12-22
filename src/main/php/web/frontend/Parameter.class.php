<?php namespace web\frontend;

class Parameter {
  public $type, $source;

  /**
   * Creates a new parameter
   * 
   * @param  lang.Type $type
   * @param  function(web.Request, string): var $source
   */
  public function __construct($type, $source) {
    $this->type= $type;
    $this->source= $source;
  }

  /**
   * Reads parameter from request
   *
   * @param  web.Request $request
   * @param  string $name
   * @return var
   */
  public function __invoke($request, $name) {
    return ($this->source)($request, $name);
  }
}