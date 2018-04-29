<?php namespace web\frontend\unittest\actions;

class Home {

  #[@get]
  public function get() {
    return ['home' => true];
  }
}