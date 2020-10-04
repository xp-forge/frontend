<?php namespace web\frontend\unittest\actions;

use web\frontend\{Get, Handler};

#[Handler('/blogs')]
class Blogs {

  #[Get('/{category}/{id:[0-9]+}')]
  public function article($category, $id) {
    return ['category' => $category, 'article' => (int)$id];
  }
}