<?php namespace web\frontend\unittest\actions;

class Home {

  #[@get, @$req: request]
  public function get($req) {
    return ['home' => $req->cookie('test')];
  }
}