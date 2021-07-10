Web frontends change log
========================

## ?.?.? / ????-??-??

## 3.5.0 / 2021-07-10

* Added possibility to use placeholders in `#[Handler]` annotations
  (@thekid)

## 3.4.1 / 2021-05-16

* Fixed issue #26: Unsupported operand types: array + null - @thekid

## 3.4.0 / 2021-05-15

* Merged PR #25: Exception handling API. This allows to catch exceptions
  and render a view instead of falling back to the minimalistic default
  error pages provided by `xp-forge/web`.
  (@thekid)
* **Heads up:** Unmatched routes now result in a 404 "Not found" errors.
  Missing or mismatched CSRF tokens now yield 403 "Forbidden" - see #24
  (@thekid)

## 3.3.0 / 2021-04-30

* Added `charset=utf-8` parameter to `Content-Type` header for all text,
  XML, JSON and JavaScript files. Implements suggestion in #23.
  (@thekid)

## 3.2.0 / 2021-04-22

* Merged PR #22: If brotli extension is available, also compress to .br
  files - they're signifantly smaller!
  (@thekid)
* Used a compression level of 9 when compressing assets. Implements
  feature request #21.
  (@thekid)

## 3.1.0 / 2021-04-11

* Merged PR #20: Add ability to bundle local files - @thekid

## 3.0.1 / 2021-04-11

* Fixed issue #19: Raise 404 if folder is accessed - @thekid

## 3.0.0 / 2021-04-10

* Removed deprecated *ClassesIn* replaced by `web.frontend.HandlersIn`
  back in version 1.0.0 of this library.
  (@thekid
* **Heads up**: Dropped support for `xp-forge/web` version 1. This
  library now requires at least version 2.9.0!
  (@thekid)
* Merged PR #18: Extend AssetsFrom handler from web.handlers.FilesFrom,
  enabling asynchronous asset downloads
  (@thekid)

## 2.3.1 / 2021-04-10

* Added `Vary: Accept-Encoding` to prevent CDNs from caching incorrectly,
  see https://blog.stackpath.com/accept-encoding-vary-important/
  (@thekid)

## 2.3.0 / 2021-04-05

* Merged PR #17: Implement asset fingerprinting. This makes the bundler
  generate assets named `[name].[contenthash].[extension]`, which can
  then be delivered with *immutable* caching, see
  https://webhint.io/docs/user-guide/hints/hint-http-cache/
  (@thekid)
* Merged PR #16: Introduce globals, which are passed to the template
  context. This is a prerequisite for being able to pass the asset
  manifest to the frontend, see #15.
  (@thekid)

## 2.2.0 / 2021-04-02

* Merged PR #14: Set Cache-Control to *no-cache* & allow overwriting via
  `View::cache()`
  (@thekid)
* Merged PR #13: Add `web.handler.AssetsFrom` to serve frontend assets.
  (@thekid)
* Merged PR #12: Add `xp bundle` subcommand. This tool can compile NPM
  libraries into bundled frontend assets and serves as a lightweight
  alternative to a more complicated npm & webpack build system.
  (@thekid)

## 2.1.0 / 2021-03-20

* Added `X-Content-Type-Options: nosniff` to headers to prevent UAs
  from performing guesswork. See https://mimesniff.spec.whatwg.org/ and
  https://webhint.io/docs/user-guide/hints/hint-x-content-type-options/
  (@thekid)

## 2.0.0 / 2020-04-10

* Implemented xp-framework/rfc#334: Drop PHP 5.6:
  . **Heads up:** Minimum required PHP version now is PHP 7.0.0
  . Rewrote code base, grouping use statements
  . Converted `newinstance` to anonymous classes
  . Rewrote `isset(X) ? X : default` to `X ?? default`
  (@thekid)

## 1.0.2 / 2020-04-05

* Implemented xp-framework/rfc#334: Remove deprecated key/value pair
  annotation syntax
  (@thekid)

## 1.0.1 / 2019-11-30

* Made compatible with XP 10 - @thekid

## 1.0.0 / 2019-09-16

* Merged PR #10: Handlers. **Heads up**: This deprecates the `ClassesIn`
  loader! Refactoring code means replacing *ClassesIn* with *HandlersIn*
  inside the application and adding the `@handler` annotation to all
  handler classes.
  (@thekid)

## 0.7.1 / 2019-09-16

* Added PHP 7.4 support - @thekid
* Fixed wrapped exceptions' stacktraces from appearing. See PR #9 for
  discussions, examples and the (easy) fix.
  (@thekid, @johannes85)

## 0.7.0 / 2018-11-02

* Merged PR #8: Request in templates - @johannes85, @thekid

## 0.6.0 / 2018-10-19

* Added possibility to inject request by using `request` as parameter
  annotation
  (@johannes85, @thekid)

## 0.5.0 / 2018-10-10

* Merged PR #6: Allows to throw web.Error in handler - @johannes85

## 0.4.1 / 2018-04-29

* Fixed patterns to always be applied in order of their length, longest
  patterns first
  (@thekid)

## 0.4.0 / 2018-04-29

* Merged PR #5: Delegates; adding shorthand alternative to manually
  entering all routes
  (@thekid)
* Added support for patterns in path segments, e.g. `/users/{id:[0-9]+}`
  (@thekid)

## 0.3.1 / 2018-04-29

* Fixed issue #3: Two named subpatterns have the same name - @thekid

## 0.3.0 / 2018-04-10

* Changed dependency on `xp-forge/web` to version 1.0.0 since it has
  been released
  (@thekid)

## 0.2.0 / 2018-04-03

* Changed parameter annotations parsing to no longer be performed for
  every request, instead lazily initialize on first use; then cache.
  See https://nikic.github.io/2014/02/18/Fast-request-routing-using-regular-expressions.html
  (@thekid)
* Made HTTP response headers controllable via `View::header()` - @thekid
* Made HTTP response status controllable via `View::status()` - @thekid

## 0.1.0 / 2018-04-02

* Hello World! First release - @thekid