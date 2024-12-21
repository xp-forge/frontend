<?php namespace web\frontend\unittest;

use test\{Assert, Test, Values};
use util\Date;
use web\frontend\View;

class ViewTest {

  /** @return iterable */
  private function modifications() {

    // Current date
    yield [null, gmdate('D, d M Y H:i:s \G\M\T')];

    // Reference date
    $date= 'Mon, 25 Nov 2024 19:30:00 GMT';
    yield [strtotime($date), $date];
    yield [$date, $date];
    yield [new Date($date), $date];
  }

  #[Test]
  public function template() {
    Assert::equals('test', View::named('test')->template);
  }

  #[Test]
  public function fragment_null_by_default() {
    Assert::null(View::named('test')->fragment);
  }

  #[Test]
  public function fragment() {
    Assert::equals('inline', View::named('test')->fragment('inline')->fragment);
  }

  #[Test]
  public function template_and_fragment_separated_with_hash() {
    $view= View::named('test#inline');
    Assert::equals('test', $view->template);
    Assert::equals('inline', $view->fragment);
  }

  #[Test]
  public function default_status() {
    Assert::equals(200, View::named('test')->status);
  }

  #[Test]
  public function status() {
    Assert::equals(201, View::named('test')->status(201)->status);
  }

  #[Test]
  public function default_content_type() {
    Assert::equals(
      'text/html; charset='.\xp::ENCODING,
      View::named('test')->headers['Content-Type']
    );
  }

  #[Test]
  public function empty() {
    Assert::null(View::empty()->template);
  }

  #[Test]
  public function header() {
    Assert::equals(
      'image/png',
      View::named('test')->header('Content-Type', 'image/png')->headers['Content-Type']
    );
  }

  #[Test, Values(from: 'modifications')]
  public function modified($date, $expected) {
    Assert::equals($expected, View::named('test')->modified($date)->headers['Last-Modified']);
  }

  #[Test]
  public function redirect_sets_location_and_status() {
    $redirect= View::redirect('http://test');

    Assert::equals(302, $redirect->status);
    Assert::equals('http://test', $redirect->headers['Location']);
  }

  #[Test]
  public function redirect_using_307() {
    $redirect= View::redirect('http://test', 307);

    Assert::equals(307, $redirect->status);
    Assert::equals('http://test', $redirect->headers['Location']);
  }

  #[Test]
  public function error_sets_template_and_status() {
    $redirect= View::error(404);

    Assert::equals(404, $redirect->status);
    Assert::equals('errors/404', $redirect->template);
  }

  #[Test]
  public function error_uses_template_and_sets_status() {
    $redirect= View::error(404, 'not-found');

    Assert::equals(404, $redirect->status);
    Assert::equals('errors/not-found', $redirect->template);
  }

  #[Test]
  public function context_empty_by_default() {
    Assert::equals([], View::named('test')->context);
  }

  #[Test]
  public function context() {
    Assert::equals(['test' => true], View::named('test')->with(['test' => true])->context);
  }

  #[Test]
  public function no_cache_by_default() {
    Assert::equals('no-cache', View::named('test')->headers['Cache-Control']);
  }

  #[Test]
  public function cache() {
    Assert::equals('max-age=604800', View::named('test')->cache('max-age=604800')->headers['Cache-Control']);
  }
}