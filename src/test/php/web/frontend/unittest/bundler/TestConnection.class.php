<?php namespace web\frontend\unittest\bundler;

use io\streams\MemoryInputStream;
use peer\http\{HttpConnection, HttpRequest, HttpResponse};

class TestConnection extends HttpConnection {
  private $response;

  public function __construct($status, $headers= [], $payload= '') {
    parent::__construct('http://test');

    $this->response= "HTTP/1.1 {$status} Test\r\n";
    foreach ($headers as $name => $value) {
      $this->response.= $name.': '.$value."\r\n";
    }
    $this->response.= "\r\n{$payload}";
  }

  public function send(HttpRequest $request) {
    return new HttpResponse(new MemoryInputStream($this->response));
  }
}