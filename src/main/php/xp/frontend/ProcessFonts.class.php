<?php namespace xp\frontend;

use io\streams\{InputStream, Streams};
use io\{File, Folder};
use util\URI;

/** @see https://developers.google.com/fonts/docs/css2 */
class ProcessFonts {
  private $files;

  /** @param xp.frontend.Files */
  public function __construct($files) {
    $this->files= $files;
  }

  public function process(Result $result, InputStream $stream, URI $uri) {
    $bytes= Streams::readAll($stream);

    // Download fonts
    preg_match_all('/url\(([^)]+)\)/', $bytes, $resources, PREG_SET_ORDER);
    foreach ($resources as $resource) {
      $uri= new URI(trim($resource[1], '"\''));
      $bundle= $this->files->store(
        $result->fetch($stream->origin->resolve($uri), !$stream->cached()),
        $uri->path()
      );

      // Update CSS with stored file's filename
      $bytes= str_replace($resource[0], 'url('.$bundle->name().')', $bytes);
    }

    $result->concat('css', $bytes);
  }
}