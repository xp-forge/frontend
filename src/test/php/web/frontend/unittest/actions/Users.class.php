<?php namespace web\frontend\unittest\actions;

use web\Error;
use web\frontend\{View, Handler, Get, Post, Param};

#[Handler]
class Users {
  private $list= [
    1 => ['id' => 1, 'name' => 'Test'],
  ];

  #[Get('/users')]
  public function all(
    #[Param]
    $start= 0,
    #[Param]
    $max= -1
  ) {
    if (-1 === $max) {
      return ['start' => $start, 'max' => $max, 'list' => array_slice($this->list, $start)];
    } else {
      return ['start' => $start, 'max' => $max, 'list' => array_slice($this->list, $start, $max)];
    }
  }

  #[Get('/users/{id}')]
  public function find($id) {
    if (0 == $id) {
      return View::redirect('/users/'.key($this->list));
    } else if (!isset($this->list[$id])) {
      return View::named('no-user')->status(404)->with(['id' => $id]);
    } else {
      return View::named('users')->header('X-User-ID', $id)->with($this->list[$id]);
    }
  }

  #[Post('/users')]
  public function create(
    #[Param]
    $username
  ) {
    if (!preg_match('/^[a-z0-9.]{3,}$/i', $username)) {
      throw new Error(400, 'Illegal username "'.$username.'"');
    }

    $id= sizeof($this->list) + 1;
    $this->list[]= ['id' => $id, 'name' => $username];
    return ['created' => $id];
  }

  #[Get('/users/{id}/avatar')]
  public function avatar($id) {
    if (!isset($this->list[$id])) {
      throw new Error(404, 'No such user '.$id);
    }
    return $this->list[$id]['avatar'];  // Raises an exception if key is undefined!
  }
}