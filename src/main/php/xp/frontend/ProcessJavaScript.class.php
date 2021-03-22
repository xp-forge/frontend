<?php namespace xp\frontend;

use io\streams\Streams;
use util\URI;

class ProcessJavaScript {
  const SOURCEMAPS = '/\/\/# sourceMappingURL=([^\n]+)/';

  public function process(URI $base, $stream) {
    yield 'concat' => ['js', preg_replace(self::SOURCEMAPS, '', Streams::readAll($stream))];
  }
}