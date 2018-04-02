Web frontends change log
========================

## ?.?.? / ????-??-??

* Changed parameter annotations parsing to no longer be performed for
  every request, instead lazily initialize on first use; then cache.
  See https://nikic.github.io/2014/02/18/Fast-request-routing-using-regular-expressions.html
  (@thekid)
* Made HTTP response headers controllable via `View::header()` - @thekid
* Made HTTP response status controllable via `View::status()` - @thekid

## 0.1.0 / 2018-04-02

* Hello World! First release - @thekid