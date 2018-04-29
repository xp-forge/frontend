<?php namespace web\frontend\unittest;

class Blogs {

  #[@get('/blogs/{category}/{id:[0-9]+}')]
  public function article($category, $id) {
    return ['category' => $category, 'article' => (int)$id];
  }
}