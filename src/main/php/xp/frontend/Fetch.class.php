<?php namespace xp\frontend;

use io\{File, Folder};
use lang\IllegalArgumentException;
use peer\http\HttpConnection;
use util\URI;

class Fetch {
  const HTTPDATE = 'D, d M Y H:i:s T';

  private $cache, $force, $progress;

  /**
   * Creates HTTP client
   *
   * @param  io.Folder|string $cache
   * @param  bool $force Whether to disable cache
   * @param  [:callable] $progress
   */
  public function __construct($cache, $force, $progress) {
    $this->cache= $cache instanceof Folder ? $cache : new Folder($cache);
    $this->force= $force;
    $this->progress= $progress;
  }

  /**
   * Fetches a response
   *
   * @param  string|util.URI $url
   * @param  bool $revalidate
   * @return xp.frontend.Response
   */
  public function get($url, $revalidate= true) {
    $uri= $url instanceof URI ? $url : new URI($url);
    $c= new HttpConnection($uri);

    $stored= new File($this->cache, 'fetch-'.md5($uri));
    if (!$stored->exists() || $this->force) {
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
      return new Cached($uri, $stored->in(), false, $this->progress);
    }

    $status= $r->statusCode();
    if (200 === $status) {
      $stored->seek(0);
      $stored->writeLine($r->header('ETag')[0]);
      return new Download($uri, new Transfer($r->in(), $stored->out()), $this->progress);
    } else if (304 === $status) {
      return new Cached($uri, $stored->in(), true, $this->progress);
    } else {
      throw new IllegalArgumentException($status.' '.$r->message());
    }
  }
}