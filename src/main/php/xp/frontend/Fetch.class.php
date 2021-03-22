<?php namespace xp\frontend;

use io\{File, Folder};
use lang\IllegalArgumentException;
use peer\http\HttpConnection;

class Fetch {
  const HTTPDATE = 'D, d M Y H:i:s T';

  public function __construct($cache, $progress) {
    $this->cache= $cache instanceof Folder ? $cache : new Folder($cache);
    $this->progress= $progress;
  }

  public function get($url, $revalidate= true) {
    $c= new HttpConnection($url);

    $stored= new File($this->cache, 'fetch-'.md5($url));
    if (!$stored->exists()) {
      $stored->open(File::WRITE);
      $r= $c->get();
    } else if ($revalidate) {
      $stored->open(File::READWRITE);
      $stored->seek(0);
      $etag= $stored->readLine();
      $r= $c->get('', ['If-None-Match' => $etag, 'If-Modified-Since' => gmdate(self::HTTPDATE, $stored->lastModified())]);
    } else {
      $stored->open(File::READ);
      $stored->readLine();
      $this->progress['cached'](false);
      return $stored->in();
    }

    $status= $r->statusCode();
    if (200 === $status) {
      $stored->seek(0);
      $stored->writeLine($r->header('ETag')[0]);
      return new Transfer($r->in(), $stored, $this->progress);
    } else if (304 === $status) {
      $this->progress['cached'](true);
      return $stored->in();
    } else {
      throw new IllegalArgumentException($status.' '.$r->message());
    }
  }
}