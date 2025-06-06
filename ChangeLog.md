Web frontends change log
========================

## ?.?.? / ????-??-??

## 7.1.1 / 2025-05-26

* Supported specifying SSL/TLS protocol by using e.g. `https+tlsv12://`
  in the URLs. Workaround for CloudFlare raising "403 Forbidden" errors
  when OpenSSL negotiates the TLS version; see issue #51.
  (@thekid)

## 7.1.0 / 2025-05-18

* Refactored code base to use the `web.io.StaticContent` class instead
  of inheriting from the *FilesFrom* handler.
  (@thekid)

## 7.0.0 / 2025-05-04

* Made it possible to use `web.frontend.Security` instances for assets
  (@thekid)
* Implemented default CSP in `web.frontend.AssetsFrom` to prevent XSS
  with SVG files. See #49 and https://stackoverflow.com/q/10557137
  (@thekid)
* **Heads up:** Dropped support for PHP < 7.4, see xp-framework/rfc#343
  (@thekid)
* Added PHP 8.5 to test matrix - @thekid

## 6.4.0 / 2024-12-21

* Added `web.frontend.View::type()` to set `Content-Type` header easily
  (@thekid)
* Merged PR #50: Add support for setting the `Last-Modified` header easily
  (@thekid)

## 6.3.0 / 2024-05-20

* Merged PR #48: Implement `View::dispatch()`, which redirects internally
  and dispatches the request to another route, bumping the dependency on
  the `web` library to https://github.com/xp-forge/web/releases/tag/v4.2.0
  (@thekid)

## 6.2.0 / 2024-03-24

* Added dependency on `xp-forge/compression`, see xp-framework/rfc#342
  (@thekid)
* Made compatible with XP 12 - @thekid

## 6.1.0 / 2024-01-30

* Made this library compatible with xp-forge/web version 4.0 - @thekid

## 6.0.0 / 2023-12-25

* Implemented xp-framework/rfc#341: Drop support for XP 9 - @thekid
* Merged PR #47: Support unmarshalling handler parameters - @thekid

## 5.6.0 / 2023-11-18

* Merged PR #46: Allow specifying status code in `View::redirect()`
  (@thekid)
* Merged PR #45: Add `View::empty()` to create a view which will not
  be rendered
  (@thekid)
* Merged PR #44: Allow specifying template *and* fragment separated
  by `#` in `View::named()`.
  (@thekid)

## 5.5.0 / 2023-10-31

* Merged PR #43: Support compression when downloading (in bundler)
  (@thekid)

## 5.4.0 / 2023-10-30

* Merged PR #42: Add support for special `_method` field to overwrite
  POST for routing
  (@thekid)

## 5.3.0 / 2023-10-29

* Merged PR #40: Support `X-Csrf-Token` header (in addition to passing
  `token` via payload). This makes integrating with frameworks such as
  [HTMX](https://htmx.org/) easier. 
  (@thekid)

## 5.2.0 / 2023-10-15

* Merged PR #39: Support multiple sources in web.frontend.AssetsFrom
  (@thekid)
* Added PHP 8.4 to the test matrix - @thekid

## 5.1.0 / 2023-07-29

* Merged PR #38: Make it possible to select a template fragment. The
  Handlebars template engine implements this, for example. See the PR
  xp-forge/handlebars-templates#14
  (@thekid)

## 5.0.0 / 2023-07-22

* Merged PR #37: Migrate to new reflection library. This introduces
  BC breaks in several cases, which may be regarded *internal* APIs,
  but might have also been used by the outside.
  (@thekid)

## 4.3.0 / 2023-07-18

* Added possibility to explicitely pass target delegate and parameters
  to frontend handling, bypassing the request URI based routing logic.
  (@thekid)
* Added accessors for delegates and templates to `Frontend` - @thekid
* Merged PR #36: Migrate to new testing library - @thekid

## 4.2.1 / 2022-12-19

* Fixed *Creation of dynamic property* errors in PHP 8.2 - @thekid

## 4.2.0 / 2022-11-13

* Merged PR #35: Handle HEAD requests via GET unless explicitely handled
  (@thekid)

## 4.1.0 / 2022-11-03

* Merged PR #33: Support glob patterns and directories for local
  dependencies, see https://www.php.net/glob
  (@thekid)

## 4.0.0 / 2022-10-29

* Merged PR #32: Remove error handling constructor parameter - @thekid
* Merged PR #31: Implement default security policies, setting the headers
  `X-Content-Type-Options`, `X-Frame-Options` and `Referrer-Policy` to
  sensible default values and providing a way to define a Content Security
  Policy. See https://github.com/xp-forge/frontend#security and issue #30
  (@thekid)

## 3.8.0 / 2022-09-14

* Merged PR #29: Compress assets downloaded as dependencies - @thekid

## 3.7.0 / 2022-09-13

* Merged PR #28: Add support for bundling web fonts - @thekid

## 3.6.2 / 2021-10-21

* Made library compatible with XP 11, `xp-forge/json` version 5.0.0
  (@thekid)

## 3.6.1 / 2021-09-26

* Made compatible with XP web 3.0, see xp-forge/web#83 - @thekid

## 3.6.0 / 2021-07-11

* Merged PR #27: Add support for remote dependencies - @thekid

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