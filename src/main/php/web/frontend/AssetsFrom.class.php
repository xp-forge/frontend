<?php namespace web\frontend;

use io\Path;
use util\MimeType;
use web\{Handler, Headers};

/**
 * Serves assets from a given path. Checks for files with extensions matching
 * the encodings passed in the `Accept-Encoding` header before falling back to
 * the original file name.
 *
 * @test web.frontend.unittest.AssetsFromTest
 * @see  https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Encoding
 */
class AssetsFrom implements Handler {
  const EXTENSIONS = [
    'br'       => '.br',
    'gzip'     => '.gz',
    'deflate'  => '.dfl',
    'bzip2'    => '.bz2',
    'identity' => '',
    '*'        => ''
  ];

  private $path;

  /**
   * Instantiate an asset handler. Serves assets from the given path, using
   * a given maximum age (in seconds) for the cache control header.
   *
   * @param  io.Path|io.Folder|string $path
   */
  public function __construct($path) {
    $this->path= $path instanceof Path ? $path : new Path($path);
  }

  /**
   * Returns encodings accepted by the client ordered by given qvalues.
   * Guarantees a "*" value to exist, which selects the uncompressed file.
   *
   * @param  string $header
   * @return [:float]
   * @see    https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Accept-Encoding
   */
  public static function accepted($header) {
    $r= [];
    $o= 0;
    $s= 1.0;
    while ($o < strlen($header)) {
      $o+= ' ' === $header[$o];
      $p= strcspn($header, ',;', $o);
      $value= substr($header, $o, $p);
      $o+= $p;

      if (';' === ($header[$o] ?? null)) {
        $p= strcspn($header, ',', $o);
        sscanf(substr($header, $o + 1, $p - 1), 'q=%f', $q);
        $o+= $p;
      } else {
        $q= $s-= 0.01;
      }
      $o++;
      $r[$value]= $q;
    }

    $r+= ['*' => 0.01];
    arsort($r, SORT_NUMERIC);
    return $r;
  }

  /**
   * Handling implementation, serves files including handling of conditional
   * `If-Modified-Since` logic.
   *
   * @param  web.Request $request
   * @param  web.Response $response
   * @return var
   */
  public function handle($request, $response) {
    $path= $request->uri()->path();
    $file= null;
    foreach (self::accepted($request->header('Accept-Encoding', '')) as $encoding => $q) {
      $target= new Path($this->path, $path.(self::EXTENSIONS[$encoding] ?? '*'));
      if ($target->exists()) {
        $file= $target->asFile();
        '*' === $encoding || $response->header('Content-Encoding', $encoding);
        break;
      }
    }

    if (null === $file) {
      $response->answer(404, 'Not Found');
      $response->send('The asset \''.$path.'\' was not found', 'text/plain');
      return;
    }

    $modified= $file->lastModified();
    if (($conditional= $request->header('If-Modified-Since')) && $modified <= strtotime($conditional)) {
      $response->answer(304, 'Not Modified');
      $response->flush();
      return;
    }

    $response->answer(200, 'OK');
    $response->header('Last-Modified', gmdate('D, d M Y H:i:s T', $modified));
    $response->header('X-Content-Type-Options', 'nosniff');
    $response->transfer($file->in(), MimeType::getByFileName($path), $file->size());
  }
}