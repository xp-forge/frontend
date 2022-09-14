<?php namespace xp\frontend;

use io\streams\{Streams, InputStream};
use io\{File, Folder};
use util\URI;

class ProcessStylesheet {
  private $files;

  /** @param xp.frontend.Files */
  public function __construct($files) {
    $this->files= $files;
  }

  public function process(Result $result, InputStream $stream, URI $uri= null) {
    $bytes= Streams::readAll($stream);

    // Download dependencies. If the stylesheet itself was read from cache, don't
    // revalidate all dependencies, instead preferring the cached copy.
    preg_match_all('/url\(([^)]+)\)/', $bytes, $resources, PREG_SET_ORDER);
    foreach ($resources as $resource) {
      $uri= new URI(trim($resource[1], '"\''));
      if ($uri->isRelative()) {
        $bundle= $this->files->store(
          $result->fetch($stream->origin->resolve($uri), !$stream->cached()),
          $uri->path()
        );

        // Update CSS with stored bundle's filename
        $bytes= str_replace($resource[0], 'url('.$bundle->name().')', $bytes);
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