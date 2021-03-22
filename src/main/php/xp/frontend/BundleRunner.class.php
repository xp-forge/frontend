<?php namespace xp\frontend;

use io\streams\{InputStream, Streams, StreamTransfer};
use io\{File, Folder};
use lang\{Environment, IllegalArgumentException};
use peer\http\{HttpConnection, HttpRequest};
use text\json\{Json, FileInput, StreamInput};
use util\URI;
use util\cmd\Console;

/**
 * Bundle assets
 * =============
 * Inside your Composer file, add the following alongside "require":
 * `
 *   "require-assets": {
 *     "bundle": {
 *       "handlebars@^4.7": ["dist/handlebars.min.js"],
 *       "simplemde@^1.11": ["dist/simplemde.min.js", "dist/simplemde.min.css"]
 *       "transliteration@^2.1": ["dist/browser/bundle.umd.min.js"]
 *     }
 *   }`
 *
 * - Bundle libraries into the given target directory
 *   ```sh
 *   $ xp bundle src/main/webapp/static
 *   ```
 * - Use supplied configuration file instead of `./composer.json`
 *   ```sh
 *   $ xp bundle -c ../composer.json dist
 *   ```
 * 
 * This will create `bundle`.js and `bundle`.css from the given libraries and
 * place them in the given target directory.
 */
class BundleRunner {

  /** Displays error message */
  private static function error(int $code, string $message): int {
    Console::$err->writeLine("\e[31mError: $message\e[0m");
    return $code;
  }

  /** Entry point */
  public static function main(array $args): int {
    $config= 'composer.json';
    $target= 'static';
    for ($i= 0, $s= sizeof($args); $i < $s; $i++) {
      if ('-c' === $args[$i]) {
        $config= $args[++$i];
      } else {
        $target= $args[$i];
      }
    }

    if ('-' === $config) {
      $input= new StreamInput(Console::$in->stream());
    } else if (is_dir($config)) {
      $input= new FileInput($config.DIRECTORY_SEPARATOR.'composer.json');
    } else if (is_file($config)) {
      $input= new FileInput($config);
    } else {
      return self::error(2, 'No configuration file found, tried '.$config);
    }

    if (!($require= Json::read($input)['require-assets'] ?? null)) {
      return self::error(1, 'No assets found in '.$config);
    }

    Console::writeLine($require);
    return 0;
  }
}