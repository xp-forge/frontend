<?php namespace xp\frontend;

use io\{File, Folder};
use lang\IllegalArgumentException;
use peer\http\HttpConnection;
use util\URI;

class Fetch {
  const HTTPDATE = 'D, d M Y H:i:s T';

  private $cache, $force;

  /**
   * Creates HTTP client
   *
   * @param  io.Folder|string $cache
   * @param  bool $force Whether to disable cache
   */
  public function __construct($cache, $force) {
    $this->cache= $cache instanceof Folder ? $cache : new Folder($cache);
    $this->force= $force;
  }

  /**
   * Fetches a response
   *
   * @param  string|util.URI $url
   * @param  [:string] $headers
   * @param  bool $revalidate
   * @param  [:callable] $progress
   * @return xp.frontend.Response
   */
  public function get($url, $headers= [], $revalidate= true, $progress= []) {
    if ($f= $progress['start'] ?? null) $f($url);

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
      $r= $c->get('', $headers + [
        'If-None-Match'     => $etag,
        'If-Modified-Since' => gmdate(self::HTTPDATE, $stored->lastModified())
      ]);
    } else {
      $stored->open(File::READ);
      $stored->readLine();
      return new Cached($uri, $stored->in(), false, $progress);
    }

    $status= $r->statusCode();
    if (200 === $status) {
      $stored->seek(0);
      $stored->truncate($r->header('Content-Length')[0] ?? 0);
      $stored->writeLine($r->header('ETag')[0] ?? '');
      return new Download($uri, new Transfer($r->in(), $stored->out()), $progress);
    } else if (304 === $status) {
      return new Cached($uri, $stored->in(), true, $progress);
    } else {
      throw new IllegalArgumentException($status.' '.$r->message());
    }
  }
}