<?php namespace web\frontend;

use lang\reflect\Package;

/**
 * Creates routing based on classes annotated with `@handler` in a
 * given package.
 *
 * @test  xp://web.frontend.unittest.HandlersInTest
 */
class HandlersIn extends Delegates {

  /**
   * Creates this delegates instance
   *
   * @param  lang.reflect.Package|string $package
   * @param  function(lang.XPClass): object $new Optional function to create instances
   */
  public function __construct($package, $new= null) {
    $p= $package instanceof Package ? $package : Package::forName($package);
    foreach ($p->getClasses() as $class) {
      if ($class->hasAnnotation('handler')) {
        $this->with($new ? $new($class) : $class->newInstance(), (string)$class->getAnnotation('handler'));
      }
    }
    uksort($this->patterns, function($a, $b) { return strlen($b) - strlen($a); });
  }
}