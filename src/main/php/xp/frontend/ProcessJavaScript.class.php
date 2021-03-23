<?php namespace xp\frontend;

use io\streams\{Streams, InputStream};
use util\URI;

class ProcessJavaScript {
  const SOURCEMAPS = '/\/\/# sourceMappingURL=([^\n]+)/';

  public function process(Result $result, InputStream $stream, URI $uri= null) {
    $result->concat('js', preg_replace(self::SOURCEMAPS, '', Streams::readAll($stream)));
  }
}