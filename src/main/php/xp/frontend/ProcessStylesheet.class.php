<?php namespace xp\frontend;

use io\streams\Streams;
use util\URI;

class ProcessStylesheet {

  public function process(Result $result, URI $base, $stream) {
    $bytes= Streams::readAll($stream);

    // Download dependencies. If the stylesheet itself was read from cache, don't
    // revalidate all dependencies, instead preferring the cached copy.
    preg_match_all('/url\(([^)]+)\)/', $bytes, $resources, PREG_SET_ORDER);
    foreach ($resources as $resource) {
      $uri= new URI($resource[1]);
      if ($uri->isRelative()) {
        $result->fetch($base->resolve($uri), !$stream->cached(), '.../'.$resource[1]);
      }
    }

    // Reorder all imports to top of CSS file
    preg_match_all('/@import url\(([^)]+)\);/', $bytes, $imports, PREG_SET_ORDER);
    foreach ($imports as $import) {
      $result->prefix('css', $import[0]);
      $bytes= str_replace($import[0], '', $bytes);
    }

    $result->concat('css', $bytes);
  }
}