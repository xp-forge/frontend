<?php namespace web\frontend\unittest\actions;

use web\frontend\{Get, Handler};

#[Handler('/oauth/{tenant}/select')]
class Select {

  #[Get]
  public function list($tenant) {
    return ['tenant' => $tenant];
  }
}