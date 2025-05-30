<?php namespace web\frontend;

use io\Path;
use util\MimeType;
use web\Handler;
use web\io\StaticContent;

/**
 * Serves assets from a given path. Checks for files with extensions matching
 * the encodings passed in the `Accept-Encoding` header before falling back to
 * the original file name.
 *
 * @test web.frontend.unittest.AssetsFromTest
 * @see  https://webhint.io/docs/user-guide/hints/hint-http-compression/
 * @see  https://www.rootusers.com/gzip-vs-bzip2-vs-xz-performance-comparison/
 * @see  https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Encoding
 */
class AssetsFrom implements Handler {
  const PREFERENCE= ['br', 'bzip2', 'gzip', 'deflate'];
  const POLICY= ['script-src' => "'none'", 'object-src' => "'none'"];
  const ENCODINGS= [
    'br'       => '.br',
    'bzip2'    => '.bz2',
    'gzip'     => '.gz',
    'deflate'  => '.dfl',
    'identity' => '',
    '*'        => ''
  ];

  private $content, $security, $preference;
  private $sources= [];

  /** @param io.Path|io.Folder|string|io.Path[]|io.Folder[]|string[] $sources */
  public function __construct($sources) {
    $this->content= new StaticContent();
    $this->security= (new Security())->csp(self::POLICY);
    $this->preferring(self::PREFERENCE);
    foreach (is_array($sources) ? $sources : [$sources] as $source) {
      $this->sources[]= $source instanceof Path ? $source : new Path($source);
    }
  }

  /**
   * Adds headers to successful responses, either from an array or a function.
   *
   * @param  [:string]|function(util.URI, io.File, string): iterable $headers
   * @return self
   */
  public function with($headers) {
    $this->content->with($headers);
    return $this;
  }

  /** Overwrites security */
  public function enacting(Security $security): self {
    $this->security= $security;
    return $this;
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
   * Determines the mime type for a given path, adding charset to mime type for
   * JavaScript, JSON, XML and all text types using the `util.MimeType` class.
   *
   * @see    https://webhint.io/docs/user-guide/hints/hint-content-type/
   * @see    https://stackoverflow.com/q/3272534
   * @param  string $path
   * @return string
   */
  public function mime($path) {
    $mime= MimeType::getByFileName($path);
    if (preg_match('#^(text/.*|image/svg\+xml|application/(javascript|json|xml|.+\+(json|xml)))$#', $mime)) {
      $mime.= '; charset='.\xp::ENCODING;
    }
    return $mime;
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
    $accept= $this->negotiate($request->header('Accept-Encoding') ?? '');
    foreach ($this->sources as $source) {

      // Check all variants in Accept-Encoding, including `*`
      foreach ($accept as $encoding => $q) {
        $target= new Path($source, $path.(self::ENCODINGS[$encoding] ?? '*'));
        if ($target->exists() && $target->isFile()) {
          $response->header('Vary', 'Accept-Encoding');
          '*' === $encoding || $response->header('Content-Encoding', $encoding);
          foreach ($this->security->headers() as $name => $value) {
            $response->header($name, $value);
          }

          return $this->content->serve($request, $response, $target->asFile(), $this->mime($path));
        }
      }
    }

    // Target does not exist in any of the sources, generate a 404
    return $this->content->serve($request, $response, null);
  }
}