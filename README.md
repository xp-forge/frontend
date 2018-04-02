Web frontends
=============

[![Build Status on TravisCI](https://secure.travis-ci.org/xp-forge/frontend.svg)](http://travis-ci.org/xp-forge/frontend)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/frontendb/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Required PHP 5.6+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-5_6plus.png)](http://php.net/)
[![Supports PHP 7.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_0plus.png)](http://php.net/)
[![Supports HHVM 3.4+](https://raw.githubusercontent.com/xp-framework/web/master/static/hhvm-3_4plus.png)](http://hhvm.com/)
[![Latest Stable Version](https://poser.pugx.org/xp-forge/frontend/version.png)](https://packagist.org/packages/xp-forge/frontend)

Frontends based on `xp-forge/web`.

## Example

Frontend uses classes with methods annotated with HTTP verbs to handle routing. These methods return a context, which is passed along with the template name to the template engine.

```php
class Home {

  #[@get]
  public function get() {
    return [];
  }
}
```

Wiring it together:

```php
use web\Application;
use web\frontend\{Frontend, Templates};
use io\Path;

class Site extends Application {

  /** @return [:var] */
  protected function routes() {
    $files= new FilesFrom(new Path($this->environment->webroot(), 'src/main/webapp'));

    $templates= new class() implements Templates {
      public function write($name, $context= [], $out) {

        // TBI:
        // - Transform template named $name with context $context
        // - Write result to the OutputStream $out
      }
    };

    return [
      '/favicon.ico' => $files,
      '/static'      => $files,
      '/'            => new Frontend(Home::class, $templates)
    ];
  }
}
```