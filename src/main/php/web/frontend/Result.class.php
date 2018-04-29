<?php namespace web\frontend;

interface Result {

  /**
   * Transfers this result
   *
   * @param  web.Request $req
   * @param  web.Response $res
   * @param  string $base
   * @return void
   */
  public function transfer($req, $res, $base);

}