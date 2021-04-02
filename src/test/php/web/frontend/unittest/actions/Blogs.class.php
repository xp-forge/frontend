<?php namespace web\frontend\unittest\actions;

use web\frontend\{Get, Handler, View};

#[Handler('/blogs')]
class Blogs {

  #[Get]
  public function index() {
    return ['development' => [1]];
  }

  #[Get('/{category}/{id:[0-9]+}')]
  public function article($category, $id) {
    return View::named('blog')
      ->with(['category' => $category, 'article' => (int)$id])
      ->cache('max-age=2419200, must-revalidate')
    ;
  }
}