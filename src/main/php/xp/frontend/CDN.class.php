<?php namespace xp\frontend;

use util\URI;

/**
 * CDN from where to download NPM packages from. Uses the jsDelivr open
 * source content delivery network by default.
 *
 * @see  https://github.com/jsdelivr/jsdelivr
 */
class CDN {
  private $fetch, $url;

  public function __construct(Fetch $fetch, string $url= 'https://cdn.jsdelivr.net/npm/%s@%s/%s') {
    $this->fetch= $fetch;
    $this->url= $url;
  }

  /** Fetches a given URL */
  public function fetch(URI $url, bool $revalidate= true): Response {
    return $this->fetch->get($url, $revalidate);
  }

  /** Locates a file with a given library version */
  public function locate(string $library, string $version, string $file): URI {
    return new URI(sprintf($this->url, $library, $version, $file));
  }
}