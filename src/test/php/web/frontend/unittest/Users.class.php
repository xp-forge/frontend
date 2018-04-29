<?php namespace web\frontend\unittest;

use web\Error;
use web\frontend\View;
use web\frontend\Stream;

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
  public function find($id) {
    if (0 == $id) {
      return View::redirect('/users/'.key($this->list));
    } else if (!isset($this->list[$id])) {
      return View::named('no-user')->status(404)->with(['id' => $id]);
    } else {
      return View::named('users')->header('X-User-ID', $id)->with($this->list[$id]);
    }
  }

  #[@post, @$username: param]
  public function create($username) {
    if (!preg_match('/^[a-z0-9.]{3,}$/i', $username)) {
      throw new Error(400, 'Illegal username "'.$username.'"');
    }

    $id= sizeof($this->list) + 1;
    $this->list[]= ['id' => $id, 'name' => $username];
    return ['created' => $id];
  }

  #[@get('/users/{id}/avatar')]
  public function avatar($id) {
    return Stream::of('JFIF...', 'image/jpeg');
  }
}