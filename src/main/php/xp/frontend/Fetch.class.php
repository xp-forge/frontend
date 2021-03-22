<?php namespace xp\frontend;

use io\{File, Folder};
use lang\IllegalArgumentException;
use peer\http\HttpConnection;

class Fetch {
  const HTTPDATE = 'D, d M Y H:i:s T';

  private $request;

  /**
   * Creates HTTP client
   *
   * @param  ?io.Folder|?string $cache
   * @param  [:callable] $progress
   */
  public function __construct($cache, $progress) {
    if (null === $cache) {
      $this->request= function($url, $revalidate) use($progress) {
        $r= (new HttpConnection($url))->get();
        if (200 === $r->statusCode()) {
          return new Download($r->in(), $progress);
        } else {
          throw new IllegalArgumentException($r->statusCode().' '.$r->message());
        }
      };
    } else {
      $base= $cache instanceof Folder ? $cache : new Folder($cache);
      $this->request= function($url, $revalidate) use($base, $progress) {
        $c= new HttpConnection($url);
        $stored= new File($base, 'fetch-'.md5($url));
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
          return new Cached($stored->in(), false, $progress);
        }

        $status= $r->statusCode();
        if (200 === $status) {
          $stored->seek(0);
          $stored->writeLine($r->header('ETag')[0]);
          return new Download(new Transfer($r->in(), $stored->out()), $progress);
        } else if (304 === $status) {
          return new Cached($stored->in(), true, $progress);
        } else {
          throw new IllegalArgumentException($status.' '.$r->message());
        }
      };
    }
  }

  /**
   * Fetches a response
   *
   * @param  string|util.URI $url
   * @param  bool $revalidate
   * @return xp.frontend.Response
   */
  public function get($url, $revalidate= true) {
    return ($this->request)($url, $revalidate);
  }
}