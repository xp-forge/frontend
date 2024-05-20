<?php namespace web\frontend;

/** @test web.frontend.unittest.DispatchingTest */
class Dispatch extends View {
  private $uri, $params;

  /**
   * Creates a new dispatcher
   *
   * @param  string|util.URI $uri
   * @param  [:string] $params
   */
  public function __construct($uri, $params= []) {
    $this->uri= $uri;
    $this->params= $params;
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
    return $req->dispatch($this->uri, $this->params);
  }
}