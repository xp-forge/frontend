<?php namespace web\frontend;

use lang\reflection\Package;

/**
 * Creates routing based on classes annotated with the `Handler` annotation
 * in a given package.
 *
 * @test  web.frontend.unittest.HandlersInTest
 */
class HandlersIn extends Delegates {

  /**
   * Creates this delegates instance
   *
   * @param  lang.reflection.Package|string $package
   * @param  function(lang.XPClass): object $new Optional function to create instances
   */
  public function __construct($package, $new= null) {
    $p= $package instanceof Package ? $package : new Package($package);
    foreach ($p->types() as $type) {
      if ($handler= $type->annotation(Handler::class)) {
        $this->with($new ? $new($type->class()) : $type->newInstance(), (string)$handler->argument(0));
      }
    }
    uksort($this->patterns, fn($a, $b) => strlen($b) - strlen($a));
  }
}