<?php namespace xp\frontend;

use util\URI;

class CDN {
  private $fetch, $url;

  public function __construct(Fetch $fetch, string $url= 'https://cdn.jsdelivr.net/npm/%s@%s/%s') {
    $this->fetch= $fetch;
    $this->url= $url;
  }

  public function fetch(URI $url, bool $revalidate= true) {
    return $this->fetch->get($url, $revalidate);
  }

  public function locate($library, $version, $file) {
    return new URI(sprintf($this->url, $library, $version, $file));
  }
}