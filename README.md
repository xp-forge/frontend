Web frontends
=============

[![Build Status on TravisCI](https://secure.travis-ci.org/xp-forge/frontend.svg)](http://travis-ci.org/xp-forge/frontend)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
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
    return ['name' => 'World'];
  }
}
```

For the above class, the template engine will receive *home* as template name and the returned map as context. The implementation below uses the [xp-forge/handlebars](https://github.com/xp-forge/handlebars) library to transform the templates.

```php
use com\handlebarsjs\{HandlebarsEngine, FilesIn};
use io\Path;
use web\frontend\Templates;

class TemplateEngine implements Templates {
  private $backing;

  public function __construct(Path $templates) {
    $this->backing= (new HandlebarsEngine())->withTemplates(new FilesIn($templates));
  }

  public function write($name, $context, $out) {
    $this->backing->write($this->backing->load($name), $context, $out);
  }
}
```

The handlebars template is quite straight-forward:

```handlebars
<html>
  <head>
    <title>Hello {{name}}</title>
  </head>
  <body>
    <h1>Hello {{name}}</h1>
  </bod>
</html>
```

Finally, wiring it together is done in the application class, as follows:

```php
use web\Application;
use web\frontend\{Frontend, Templates};
use io\Path;

class Site extends Application {

  /** @return [:var] */
  protected function routes() {
    $files= new FilesFrom(new Path($this->environment->webroot(), 'src/main/webapp'));
    $templates= new TemplateEngine(new Path($this->environment->webroot(), 'src/main/handlebars'));

    return [
      '/favicon.ico' => $files,
      '/static'      => $files,
      '/'            => new Frontend(Home::class, $templates)
    ];
  }
}
```