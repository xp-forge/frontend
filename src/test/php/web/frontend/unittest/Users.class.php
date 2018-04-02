<?php namespace web\frontend\unittest;

class Users {
  private $list= [
    1 => ['id' => 1, 'name' => 'Test'],
  ];

  #[@get('/users'), @$start: param, @$max: param]
  public function all($start= 0, $max= -1) {
    if (-1 === $max) {
      return ['start' => $start, 'max' => $max, 'list' => array_slice($this->list, $start)];
    } else {
      return ['start' => $start, 'max' => $max, 'list' => array_slice($this->list, $start, $max)];
    }
  }

  #[@get('/users/{id}')]
  public function find($id) { return $this->list[$id]; }

  #[@post, @$username: param]
  public function create($username) {
    $id= sizeof($this->list) + 1;
    $this->list[]= ['id' => $id, 'name' => $username];
    return ['created' => $id];
  }
}