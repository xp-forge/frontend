<?php namespace xp\frontend;

use util\URI;

/** @see https://developers.google.com/fonts/docs/css2 */
class FontsDependency extends Dependency {
  private $params, $families;

  /**
   * Creates a new dependency
   *
   * @param  string|util.URI $params
   * @param  string[] $families
   */
  public function __construct($params, array $families) {
    $this->params= str_replace('fonts://', '', $params);
    $this->families= $families;
  }

  public function files() {
    foreach ($this->families as $family) {
      yield $family.'.fonts' => function($cdn) use($family) {
        return $cdn->fetch(new URI('https://fonts.googleapis.com/css2?family='.$family.'&'.$this->params));
      };
    }
  }
}