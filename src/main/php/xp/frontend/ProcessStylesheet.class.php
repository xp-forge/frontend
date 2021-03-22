<?php namespace xp\frontend;

use io\streams\Streams;
use util\URI;

class ProcessStylesheet {

  public function process(URI $base, $stream) {
    $bytes= Streams::readAll($stream);

    // Download dependencies
    preg_match_all('/url\(([^)]+)\)/', $bytes, $resources, PREG_SET_ORDER);
    foreach ($resources as $resource) {
      $uri= new URI($resource[1]);
      if ($uri->isRelative()) {
        yield 'fetch' => [$base->resolve($uri), $stream instanceof Transfer, '.../'.$resource[1]];
      }
    }

    // Reorder all imports to top of CSS file
    preg_match_all('/@import url\(([^)]+)\);/', $bytes, $imports, PREG_SET_ORDER);
    foreach ($imports as $import) {
      yield 'prefix' => ['css', $import[0]];
      $bytes= str_replace($import[0], '', $bytes);
    }

    yield 'concat' => ['css', $bytes];
  }
}