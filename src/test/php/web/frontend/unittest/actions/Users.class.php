<?php namespace web\frontend\unittest\actions;

use web\Error;
use web\frontend\View;

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

  #[@post('/users'), @$username: param]
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
    if (!isset($this->list[$id])) {
      throw new Error(404, 'No such user '.$id);
    }
    return $this->list[$id]['avatar'];  // Raises an exception if key is undefined!
  }

}