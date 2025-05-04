Web frontends
=============

[![Build status on GitHub](https://github.com/xp-forge/frontend/workflows/Tests/badge.svg)](https://github.com/xp-forge/frontend/actions)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.4+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_4plus.svg)](http://php.net/)
[![Supports PHP 8.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-8_0plus.svg)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-forge/frontend/version.svg)](https://packagist.org/packages/xp-forge/frontend)

Frontends based on `xp-forge/web`, using annotation-based routing.

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
*Note: For PHP 7, the `Param` annotation must be on a line by itself, [see here](https://gist.github.com/thekid/8ce84b0d0de8fce5b6dd5faa22e1d716#file-home-class-php)!*

For the above class, the template engine will receive *home* as template name and the returned map as context. This library contains only the skeleton for templating - the [xp-forge/handlebars-templates](https://github.com/xp-forge/handlebars-templates) library implements it. For the rest of the examples, we'll be using it.

The handlebars template *hello.handlebars* (calculated from the lowercase version of the above handler class' name) is quite straight-forward:

```handlebars
<!DOCTYPE html>
<html lang="en">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
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
package org.example.web {

  public class org.example.web.Home
  public class org.example.web.User
  public class org.example.web.Group
}
```

Then use the delegation API provided by the `HandlersIn` class:

```php
use web\frontend\{Frontend, HandlersIn};

// ...inside the routes() method, as seen above:
new Frontend(new HandlersIn('org.example.web'), $templates);
```

## Handling routes and methods

The `Handler` annotation can include a path which is used as a prefix for all method routes in a handler class. Placeholders can be used to select method parameters from the request URI.

```php
use web\frontend\{Handler, Get};

#[Handler('/hello')]
class Hello {

  #[Get]
  public function world() {
    return ['greet' => 'World'];
  }

  #[Get('/{name}')]
  public function person(string $name) {
    return ['greet' => $name];
  }
}
```

The above method routes will only accept `GET` requests. `POST` request methods can be annotated with `Post`, `PUT` with `Put`, and so on. To overwrite the method used for POST requests, pass the special `_method` field:

```html
<form action="/example" method="POST">
  <input type="hidden" name="_method" value="PUT">
  <!-- Rest of form -->
</form>
```

This will route the request as if it had been issued as `PUT /example HTTP/1.1`.

### Views 

Route methods can return `web.frontend.View` instances to have more control over the response:

```php
use web\frontend\View;

// Equivalent of the above world() method's return value
return View::named('hello')->with(['greet' => 'World']);

// Redirecting to either paths or absolute URIs
return View::redirect('/hello/World');

// No content
return View::empty()->status(204);

// Add headers and caching, here: for 7 days
return View::named('blog')
  ->with($article)
  ->header('X-Binford', '6100 (more power)')
  ->modified($modified)
  ->cache('max-age=604800, must-revalidate')
;
```

## Serving assets

Assets are delivered by the `AssetsFrom` handler as seen above. It takes care of content types, handling conditional and range requests for partial content, as well as compression.

### Sources

The constructor accepts single paths as well as an array of paths which will be searched for the requested asset. The first path to provide the asset is selected, the file being served from there.

```php
use web\frontend\AssetsFrom;

// Single source
$assets= new AssetsFrom($this->environment->path('src/main/webapp'));

// Multiple sources
$assets= new AssetsFrom([
  $this->environment->path('src/main/webapp'),
  $this->environment->path('vendor/example/layout-lib/src/main/webapp'),
]);
```

### Caching

Assets can be delivered with a `Cache-Control` header by passing it to the `with` function. In this example, assets are cached for 28 days, but clients are asked to revalidate using conditional requests before using their cached copy.

```php
use web\frontend\AssetsFrom;

$assets= (new AssetsFrom($path))->with([
  'Cache-Control' => 'max-age=2419200, must-revalidate'
]);
```

### Compression

Assets can also be delivered in compressed forms to save bandwidth. The typical bundled JavaScript library can be megabytes in raw size! By using e.g. Brotli, this can be drastically reduced to a couple of hundred kilobytes.

* The request URI is mapped to the asset file name
* If the clients sends an `Accept-Encoding` header, it is parsed and the client preference negotiated
* The server tries *[file]*.br (for Brotli), *[file]*.bz2 (for BZip2), *[file]*.gz (for GZip) and *[file]*.dfl (for Deflate), and only sends the uncompressed version if none exists nor is acceptable.

*Note: Assets are not compressed on the fly as this would cause unnecessary server load.*

### Asset fingerprinting

Generated assets can be fingerprinted by embedding a version identifier in the filename, e.g. *[file].[version].[ext]*. Every time their contents change, the version (or *fingerprint*) changes, and with it the filename. These assets can then be regarded "immutable", and served with an "infinite" maximum age. Bundlers (like Webpack or the one built-in to this library) will create an *asset manifest* along with these assets.

```php
use web\frontend\{AssetsFrom, AssetsManifest};

$manifest= new AssetsManifest($path->resolve('manifest.json'));
$assets= new AssetsFrom($path)->with(fn($uri) => [
  'Cache-Control' => $manifest->immutable($uri) ?? 'max-age=2419200, must-revalidate'
]);
```

Because mapping the filenames happens in the template engine, the manifest must also be passed there:

```php
use web\frontend\Handlebars;
use web\frontend\helpers\Assets;

$templates= new Handlebars($path, [new Assets($manifest)]);
```

The handlebars code then uses the *asset* helper to lookup the filename including the fingerprint:

```handlebars
<link href="/static/{{asset 'vendor.css'}}" rel="stylesheet">
```

*This way, we don't have to commit changes to our handlebars file every time the assets are changed, which may happen often!*

### The built-in bundler

Bundling assets makes sense from a security standpoint, but also to reduce HTTP requests. This library comes with a `bundle` subcommand, which can generate JavaScript and CSS bundles from dependencies tracked in `package.json`.

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

To create the bundles to the *src/main/webapp/static* directory and the assets manifest, run the following:

```bash
$ xp bundle -m src/main/webapp/manifest.json src/main/webapp/static
# ...
```

This will create *vendor.[fingerprint].js* and *vendor.[fingerprint].css* files as well as compressed versions (*if the zlib and [brotli](https://github.com/kjdev/php-ext-brotli) PHP extensions are available*) and the assets manifest, which maps the file names without fingerprints to those with.

The bundler can also resolve local files, URLs as well as [Google fonts](https://fonts.google.com/):

```json
{
  "bundles": {
    "vendor": {
      "src/main/js": "index.js",
      "https://cdn.amcharts.com/lib/4": "core.js | charts.js | themes/kelly.js",
      "fonts://display=swap": "Overpass"
    }
  }
}
```

## Error handling

By default, errors and exceptions will yield in a minimalistic error page with the corresponding error code (*defaulting to 500 Internal Server Error*) shown. Exceptions can be handled by a closure, a status code or by default, and decide to return a view of their own. This view is loaded from the *errors/* subfolder and passed a context of `['cause' => $exception]`.

```php
use web\frontend\{HandlersIn, Frontend, Exceptions};
use org\example\{InvalidOrder, LinkExpired};
use lang\Throwable;

$frontend= (new Frontend(new HandlersIn('org.example.web'), $templates))
  ->handling((new Exceptions())
    ->catch(InvalidOrder::class, fn($e) => View::error(503, 'invalid-order')),
    ->catch(LinkExpired::class, 404) // uses template "errors/404"
    ->catch(Throwable::class)        // catch-all, errors/{status} for web.Error, errors/500 for others
  )
;
```

Using our handlebars engine from above, the template *errors/404.handlebars* could look like this:

```handlebars
<!DOCTYPE html>
<html lang="en">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Error 404</title>
</head>
<body>
  <h1>Not found</h1>
  <p>{{cause.message}}</p>

  {{! Log errors !}}
  {{log request.uri "~" cause level="error"}}
</body>
</html>
```

## Security

This library sets the following security header defaults:

* `X-Content-Type-Options: nosniff` - prevents browsers from [MIME sniffing](https://mimesniff.spec.whatwg.org/)
* `X-Frame-Options: DENY` - prevents site from being embedded in an `<iframe>`.
* `Referrer-Policy: no-referrer-when-downgrade` - doesn't send HTTP referrer over unencrypted connections.

To configure framing, referrer and content security policies, use the *security()* fluent interface:

```php
use web\frontend\{Frontend, Security};

$policy= (new Security())
  ->framing('SAMEORIGIN')
  ->referrers('strict-origin')
  ->csp([
    'default-src' => '"none"',
    'script-src'  => ['"self"', '"nonce-{{nonce}}"', 'https://example.com'],
    // etcetera
  ])
;
$frontend= (new Frontend($delegates, $templates))->enacting($policy);
```

For static assets, the same policy can be used:

```php
use web\frontend\{AssetsFrom, Security};

$policy= /* see above */
$assets= (new AssetsFrom($path))->enacting($policy);
```

The default configuration is to set `script-src 'none'; object-src 'none'`, see https://stackoverflow.com/q/10557137

*Read more about hardening response headers at https://scotthelme.co.uk/hardening-your-http-response-headers/ or watch this talk: https://www.youtube.com/watch?v=mr230uotw-Y*

## Performance

When using the production servers, the application's code is only compiled and its setup only runs once. This gives us lightning-fast response times:

![Network console screenshot](https://github.com/user-attachments/assets/0310304b-23f8-43c9-8809-95a805fede4f)