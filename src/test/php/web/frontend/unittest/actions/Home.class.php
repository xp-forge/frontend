<?php namespace web\frontend\unittest\actions;

use web\frontend\{Get, Handler, Request};

#[Handler]
class Home {

  #[Get]
  public function get(
    #[Request]
    $req
  ) {
    return ['home' => $req->cookie('test')];
  }
}