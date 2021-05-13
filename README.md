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
class Hello {

  #[Get]
  public function greet(#[Param('name')] $param) {
    return ['name' => $param ?: 'World'];
  }
}
```

For the above class, the template engine will receive *home* as template name and the returned map as context. This library contains only the skeleton for templating - the [xp-forge/handlebars-templates](https://github.com/xp-forge/handlebars-templates) implements it.

The handlebars template is quite straight-forward:

```handlebars
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Hello World</title>
  </head>
  <body>
    <h1>Hello {{name}}</h1>
  </body>
</html>
```

Finally, wiring it together is done in the application class, as follows:

```php
use web\Application;
use web\frontend\{AssetsFrom, Frontend, Handlebars};

class Site extends Application {

  /** @return [:var] */
  public function routes() {
    $assets= new AssetsFrom($this->environment->path('src/main/webapp'));
    $templates= new Handlebars($this->environment->path('src/main/handlebars'));

    return [
      '/favicon.ico' => $assets,
      '/static'      => $assets,
      '/'            => new Frontend(new Hello(), $templates)
    ];
  }
}
```

To run it, use `xp -supervise web Site`, which will serve the site at http://localhost:8080/.

## Organizing your code

In real-life situations, you will not want to put all of your code into the `Hello` class. In order to separate code out into various classes, place all handler classes inside a dedicated package:

```bash
@FileSystemCL<./src/main/php>
package org.example.example.web {

  public class org.example.example.web.Home
  public class org.example.example.web.User
  public class org.example.example.web.Group
}
```

Then use the delegation API provided by the `HandlersIn` class:

```php
use web\frontend\{Frontend, HandlersIn};

// ...inside the routes() method, as seen above:
new Frontend(new HandlersIn('org.example.example.web'), $templates);
```

## Serving assets

Assets are delivered by the `AssetsFrom` handler as seen above. It takes care of content types, handling conditional and range requests for partial content, as well as handling compression.

### Caching

Assets can be delivered with a `Cache-Control` header by passing it to the `with` function. In this example, assets are cached for 28 days, but clients are asked to revalidate using conditional requests before using their cached copy.

```php
$assets= (new AssetsFrom($path))->with([
  'Cache-Control' => 'max-age=2419200, must-revalidate'
]);
```

### Compression

Assets can also be delivered in compressed forms to save bandwidth. The typical bundled JavaScript library can be megabytes in raw size! By using e.g. Brotli, this can be drastically reduced to a couple hundred kilobytes.

* The request URI is mapped to the asset file name
* If the clients sends an `Accept-Encoding` header, it is parsed and the client preference negotiated
* The server tries *[file]*.br (for Brotli), *[file]*.bz2 (for BZip2), *[file]*.gz (for GZip) and *[file]*.dfl (for Deflate), and only sends the uncompressed version if none exists nor is acceptable.

*Note: Assets are not compressed on the fly as this would cause unnecessary server load.*

### Asset fingerprinting

Generated assets can be fingerprinted by embedding a version identifier in the filename, e.g. *[file].[version].[ext]*. Every time their contents change, the version changes, and with it the filename. These assets can then be regarded "immutable", and served with an "infinite" maximum age. Bundlers (like Webpack or the one built-in to this library) will create an *asset manifest* along with these assets.

```php
$manifest= new AssetsManifest($path->resolve('manifest.json'));
$assets= new AssetsFrom($path)->with(fn($uri) => [
  'Cache-Control' => $manifest->immutable($uri) ?? 'max-age=2419200, must-revalidate'
]);
```

### The built-in bundler

This library comes with a `bundle` subcommand, which can generated JavaScript and CSS bundles from dependencies tracked in `package.json`.

```json
{
  "dependencies": {
    "simplemde": "^1.11",
    "transliteration": "^2.1"
  },
  "bundles": {
    "vendor": {
      "simplemde": "dist/simplemde.min.js | dist/simplemde.min.css",
      "transliteration": "dist/browser/bundle.umd.min.js"
    }
  }
}
```

To create the bundles and the assets manifest, run the following:

```bash
$ xp bundle -m src/main/webapp/manifest.json src/main/webapp/static
# ...
```

This will create *vendor.js* and *vendor.css* files as well as compressed versions (*if the zlib and [brotli](https://github.com/kjdev/php-ext-brotli) PHP extensions are available*). 

## Performance

When using the production servers, the application's code is only compiled and its setup only runs once. This gives us lightning-fast response times:

![Network console screenshot](https://user-images.githubusercontent.com/696742/114273532-adc30b00-9a1a-11eb-9267-e0ceda8d64e2.png)