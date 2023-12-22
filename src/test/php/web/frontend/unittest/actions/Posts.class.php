<?php namespace web\frontend\unittest\actions;

use web\frontend\unittest\ObjectId;
use web\frontend\{Handler, Get};

#[Handler('/posts')]
class Posts {

  #[Get('/{id}')]
  public function get(ObjectId $id) {
    return ['id' => $id->string()];
  }
}