<?php namespace web\frontend;

/** @test web.frontend.unittest.DispatchingTest */
class Dispatch extends View {
  private $uri;

  /** @param string|util.URI $uri */
  public function __construct($uri) {
    $this->uri= $uri;
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
    return $req->dispatch($this->uri);
  }
}