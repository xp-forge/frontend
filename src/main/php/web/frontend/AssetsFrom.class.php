<?php namespace web\frontend;

use io\Path;
use util\MimeType;
use web\handler\FilesFrom;

/**
 * Serves assets from a given path. Checks for files with extensions matching
 * the encodings passed in the `Accept-Encoding` header before falling back to
 * the original file name.
 *
 * @test web.frontend.unittest.AssetsFromTest
 * @see  https://www.rootusers.com/gzip-vs-bzip2-vs-xz-performance-comparison/
 * @see  https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Encoding
 */
class AssetsFrom extends FilesFrom {
  const PREFERENCE= ['br', 'bzip2', 'gzip'];
  const ENCODINGS= [
    'br'       => '.br',
    'bzip2'    => '.bz2',
    'gzip'     => '.gz',
    'deflate'  => '.dfl',
    'identity' => '',
    '*'        => ''
  ];

  private $preference;

  /** @param io.Path|io.Folder|string $path */
  public function __construct($path) {
    parent::__construct($path);
    $this->preferring(self::PREFERENCE);
  }

  /**
   * Change encoding preference
   *
   * @param  string[] $encodings
   * @return self
   */
  public function preferring($encodings) {
    $this->preference= [];
    $p= sizeof($encodings);
    foreach ($encodings as $encoding) {
      $this->preference[$encoding]= 0.01 * $p--;
    }
    return $this; 
  }


  /**
   * Negotiate encodings accepted by the client ordered by given qvalues.
   * Guarantees a "*" value to exist, which selects the uncompressed file.
   *
   * @param  string $header Accept-Encoding header sent by client
   * @return [:float]
   * @see    https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Accept-Encoding
   */
  public function negotiate($header) {
    $r= [];
    $o= 0;
    $s= 1.0;
    while ($o < strlen($header)) {
      $o+= ' ' === $header[$o];
      $p= strcspn($header, ',;', $o);
      $encoding= substr($header, $o, $p);
      $o+= $p;

      if (';' === ($header[$o] ?? null)) {
        $p= strcspn($header, ',', $o);
        sscanf(substr($header, $o + 1, $p - 1), 'q=%f', $q);
        $o+= $p;
      } else {
        $q= $s + ($this->preference[$encoding] ?? 0.0);
      }
      $o++;
      $r[$encoding]= $q;
    }

    $r+= ['*' => 0.01];
    arsort($r, SORT_NUMERIC);
    return $r;
  }

  /**
   * Handling implementation, serves files including handling of conditional
   * `If-Modified-Since` logic and partial requests.
   *
   * @param  web.Request $request
   * @param  web.Response $response
   * @return var
   */
  public function handle($request, $response) {
    $path= $request->uri()->path();
    $base= $this->path();

    // Check all variants in Accept-Encoding, including `*`
    foreach ($this->negotiate($request->header('Accept-Encoding', '')) as $encoding => $q) {
      $target= new Path($base, $path.(self::ENCODINGS[$encoding] ?? '*'));
      if ($target->exists() && $target->isFile()) {
        $response->header('Vary', 'Accept-Encoding');
        '*' === $encoding || $response->header('Content-Encoding', $encoding);

        return $this->serve($request, $response, $target->asFile(), MimeType::getByFileName($path));
      }
    }

    // No target exists, generate a 404
    return $this->serve($request, $response, null);
  }
}