<?php namespace web\frontend;

/** @test web.frontend.unittest.DispatchingTest */
class Dispatch extends View {
  private $url;

  /** @param string|util.URI $uri */
  public function __construct($url) {
    $this->url= $url;
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
    return $req->dispatch($this->url);
  }
}