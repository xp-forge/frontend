<?php namespace web\frontend\unittest;

use lang\{IllegalArgumentException, Throwable};
use test\{Assert, Expect, Test};
use web\Error;
use web\frontend\{Exceptions, View};

class ExceptionsTest {

  #[Test]
  public function can_create() {
    new Exceptions();
  }

  #[Test, Expect(class: Error::class, message: 'test')]
  public function throws_error_if_not_mapped() {
    (new Exceptions())->handle(new Error(404, 'test'));
  }

  #[Test, Expect(class: Error::class, message: 'test')]
  public function throws_as_error_if_not_mapped() {
    (new Exceptions())->handle(new IllegalArgumentException('test'));
  }

  #[Test, Expect(class: Error::class, message: 'test')]
  public function callable_returning_null_will_raise() {
    (new Exceptions())
      ->catch(IllegalArgumentException::class, function($e) { return null; })
      ->handle(new IllegalArgumentException('test'))
    ;
  }

  #[Test]
  public function mapping_web_error_uses_its_statuscode() {
    $view= (new Exceptions())
      ->catch(Error::class)
      ->handle(new Error(404, 'test'))
    ;
    Assert::equals([404, 'errors/404'], [$view->status, $view->template]);
  }

  #[Test]
  public function mapping_exception_uses_500_as_statuscode() {
    $view= (new Exceptions())
      ->catch(IllegalArgumentException::class)
      ->handle(new IllegalArgumentException('test'))
    ;
    Assert::equals([500, 'errors/500'], [$view->status, $view->template]);
  }

  #[Test]
  public function mapping_exception_to_statuscode() {
    $view= (new Exceptions())
      ->catch(IllegalArgumentException::class, 400)
      ->handle(new IllegalArgumentException('test'))
    ;
    Assert::equals([400, 'errors/400'], [$view->status, $view->template]);
  }

  #[Test]
  public function mapping_exception_to_callable() {
    $view= (new Exceptions())
      ->catch(IllegalArgumentException::class, function($e) { return View::error(400, 'validation'); })
      ->handle(new IllegalArgumentException('test'))
    ;
    Assert::equals([400, 'errors/validation'], [$view->status, $view->template]);
  }

  #[Test]
  public function mapping_throwable_to_catch_all() {
    $view= (new Exceptions())
      ->catch(Throwable::class)
      ->handle(new IllegalArgumentException('test'))
    ;
    Assert::equals([500, 'errors/500'], [$view->status, $view->template]);
  }

  #[Test]
  public function order_is_relevant() {
    $view= (new Exceptions())
      ->catch(IllegalArgumentException::class, 400)
      ->catch(Throwable::class)
      ->handle(new IllegalArgumentException('test'))
    ;
    Assert::equals([400, 'errors/400'], [$view->status, $view->template]);
  }

  #[Test]
  public function parent_shadows_type() {
    $view= (new Exceptions())
      ->catch(Throwable::class)
      ->catch(IllegalArgumentException::class, 400) // Not used in this case!
      ->handle(new IllegalArgumentException('test'))
    ;
    Assert::equals([500, 'errors/500'], [$view->status, $view->template]);
  }

  #[Test]
  public function context_contains_exception() {
    $view= (new Exceptions())
      ->catch(IllegalArgumentException::class)
      ->handle(new IllegalArgumentException('test'))
    ;

    Assert::instance(IllegalArgumentException::class, $view->context['cause']);
  }

  #[Test]
  public function context_from_callables_is_merged() {
    $handler= function($e) { return View::error(400, 'validation')->with(['type' => get_class($e)]); };
    $view= (new Exceptions())
      ->catch(IllegalArgumentException::class, $handler)
      ->handle(new IllegalArgumentException('test'))
    ;

    Assert::equals(IllegalArgumentException::class, $view->context['type']);
    Assert::instance(IllegalArgumentException::class, $view->context['cause']);
  }
}