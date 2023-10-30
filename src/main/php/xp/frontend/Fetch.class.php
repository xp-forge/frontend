<?php namespace xp\frontend;

use io\streams\Compression;
use io\{File, Folder};
use lang\IllegalArgumentException;
use peer\http\HttpConnection;
use util\URI;

/** @test web.frontend.unittest.bundler.FetchTest */
class Fetch {
  const HTTPDATE = 'D, d M Y H:i:s T';

  private static $accept= '';
  private $cache, $force, $connections;

  static function __static() {
    foreach (Compression::algorithms()->supported() as $algorithm) {
      self::$accept.= $algorithm->token().', ';
    }
    self::$accept.= 'identity';
  }

  /**
   * Creates HTTP client
   *
   * @param  io.Folder|string $cache
   * @param  bool $force Whether to disable cache
   */
  public function __construct($cache, $force) {
    $this->cache= $cache instanceof Folder ? $cache : new Folder($cache);
    $this->force= $force;
    $this->connections= function($uri) { return new HttpConnection($uri); };
  }

  /**
   * Specify a connection function, which gets passed a URI and returns a
   * `HttpConnection` instance.
   *
   * @param  function(var): peer.http.HttpConnection $connections
   * @return self
   */
  public function connecting($connections) {
    $this->connections= cast($connections, 'function(var): peer.http.HttpConnection');
    return $this;
  }

  /** Returns cache file for a given URI */
  public function cache(URI $uri): File {
    return new File($this->cache, 'fetch-'.md5($uri));
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
    $c= $this->connections->__invoke($uri);

    $stored= $this->cache($uri);
    if (!$stored->exists() || $this->force) {
      $stored->open(File::WRITE);
      $r= $c->get('', $headers + ['Accept-Encoding' => self::$accept]);
    } else if ($revalidate) {
      $stored->open(File::READWRITE);
      $stored->seek(0);
      $etag= $stored->readLine();
      $r= $c->get('', $headers + [
        'Accept-Encoding'   => self::$accept,
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

      $transfer= new Transfer(
        Compression::named($r->header('Content-Encoding')[0] ?? 'none')->open($r->in()),
        $stored->out()
      );
      return new Download($uri, $transfer, $progress);
    } else if (304 === $status) {
      return new Cached($uri, $stored->in(), true, $progress);
    } else {
      throw new IllegalArgumentException($status.' '.$r->message());
    }
  }
}