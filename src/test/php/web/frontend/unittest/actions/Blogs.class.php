<?php namespace web\frontend\unittest\actions;

#[@handler('/blogs')]
class Blogs {

  #[@get('/{category}/{id:[0-9]+}')]
  public function article($category, $id) {
    return ['category' => $category, 'article' => (int)$id];
  }
}