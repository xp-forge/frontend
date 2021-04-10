Web frontends
=============

[![Build status on GitHub](https://github.com/xp-forge/frontend/workflows/Tests/badge.svg)](https://github.com/xp-forge/frontend/actions)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_0plus.svg)](http://php.net/)
[![Supports PHP 8.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-8_0plus.svg)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-forge/frontend/version.png)](https://packagist.org/packages/xp-forge/frontend)

Frontends based on `xp-forge/web`.

## Example

Frontend uses handler classes with methods annotated with HTTP verbs to handle routing. These methods return a context, which is passed along with the template name to the template engine.

```php
use web\frontend\{Handler, Get, Param};

#[Handler]
class Home {

  #[Get]
  public function get(
    #[Param('name')]
    $param
  ) {
    return ['name' => $param ?: 'World'];
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
  </body>
</html>
```

Finally, wiring it together is done in the application class, as follows:

```php
use web\Application;
use web\frontend\{AssetsFrom, Frontend, Templates};

class Site extends Application {

  /** @return [:var] */
  public function routes() {
    $assets= new AssetsFrom($this->environment->path('src/main/webapp'));
    $templates= new TemplateEngine($this->environment->path('src/main/handlebars'));

    return [
      '/favicon.ico' => $assets,
      '/static'      => $assets,
      '/'            => new Frontend(new Home(), $templates)
    ];
  }
}
```

To run it, use `xp -supervise web Site`, which will serve the site at http://localhost:8080/. Find and clone the example code [here](https://gist.github.com/thekid/8ce84b0d0de8fce5b6dd5faa22e1d716).

## Organizing your code

In real-life situations, you will not want to put all of your code into the `Home` class. In order to separate code out into various classes, place all handler classes inside a dedicated package:

```bash
@FileSystemCL<./src/main/php>
package de.thekid.example.handlers {

  public class de.thekid.example.handlers.Home
  public class de.thekid.example.handlers.User
  public class de.thekid.example.handlers.Group
}
```

Then use the delegation API provided by the `HandlersIn` class:

```php
use web\frontend\{Frontend, HandlersIn};

// ...inside the routes() method, as seen above:
new Frontend(new HandlersIn('de.thekid.example.handlers'), $templates);
```

## Performance

When using the production servers, the application's code is only compiled and its setup only runs once. This gives us lightning-fast response times:

![Network console screenshot](https://user-images.githubusercontent.com/696742/114273532-adc30b00-9a1a-11eb-9267-e0ceda8d64e2.png)