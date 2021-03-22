<?php namespace xp\frontend;

use io\Folder;
use lang\{Environment, Throwable};
use text\json\{Json, FileInput, StreamInput};
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

    $fetch= new Fetch(Environment::tempDir(), [
      'cached' => function($r) { Console::write('(cached', $r ? '' : '*', ')'); },
      'update' => function($t) { Console::writef('%d%s', $t, str_repeat("\x08", strlen($t))); },
      'final'  => function($t) { Console::writef('%s%s', str_repeat(' ', strlen($t)), str_repeat("\x08", strlen($t))); },
    ]);

    $handlers= [
      'css' => new ProcessStylesheet(),
      'js'  => new ProcessJavaScript(),
      '*'   => new StoreFile(),
    ];

    $bundler= new Bundler($fetch, new Resolver($fetch), $handlers, new Folder($target));
    $next= 0;
    $pwd= realpath('.');
    try {
      foreach ($require as $name => $spec) {
        Console::writeLine($next++ ? "\n" : '', "\e[32mGenerating ", $name, " bundle\e[0m");
        foreach ($bundler->create($name, new Dependencies($spec)) as $file) {
          Console::writeLine(str_replace($pwd, '', $file->getURI()), ': ', $file->size(), ' bytes');
        }
      }
      return 0;
    } catch (Throwable $t) {
      return self::error(8, $t->toString());
    }
  }
}