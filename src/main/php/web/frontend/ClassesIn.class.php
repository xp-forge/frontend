<?php namespace web\frontend;

use lang\reflect\Package;

/**
 * Creates routing based on classes in a given package
 *
 * @deprecated Use HandlersIn instead
 */
class ClassesIn extends Delegates {

  /**
   * Creates this delegates instance
   *
   * @param  lang.reflect.Package|string $package
   * @param  function(lang.XPClass): object $new Optional function to create instances
   */
  public function __construct($package, $new= null) {
    $p= $package instanceof Package ? $package : Package::forName($package);
    foreach ($p->getClasses() as $class) {
      if ($class->reflect()->isInstantiable()) {
        $this->with($new ? $new($class) : $class->newInstance());
      }
    }
    uksort($this->patterns, function($a, $b) { return strlen($b) - strlen($a); });
  }
}