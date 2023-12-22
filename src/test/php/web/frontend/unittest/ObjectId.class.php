<?php namespace web\frontend\unittest;

class ObjectId {
  private $id;

  public function __construct($id) {
    $this->id= (string)$id;
  }


  public function string() { return $this->id; }
}