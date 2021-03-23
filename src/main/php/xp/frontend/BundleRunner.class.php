<?php namespace xp\frontend;

use io\{File, Folder};
use lang\{Environment, Runtime, Throwable};
use text\json\{Json, FileInput, StreamInput};
use util\cmd\Console;
use util\profiling\Timer;

/**
 * Bundle assets
 * =============
 * Inside your `package.json`, add the bundle definition along dependencies:
 *
 * `{
 *   "dependencies": {
 *     "simplemde": "^1.11",
 *     "transliteration": "^2.1"
 *   },
 *   "bundles": {
 *     "bundle": {
 *       "simplemde": "dist/simplemde.min.js | dist/simplemde.min.css",
 *       "transliteration": "dist/browser/bundle.umd.min.js"
 *     }
 *   }
 * }`
 *
 * - Bundle libraries into the given target directory
 *   ```sh
 *   $ xp bundle src/main/webapp/static
 *   ```
 * - Use supplied configuration file instead of `./package.json`
 *   ```sh
 *   $ xp bundle -c ../package.json dist
 *   ```
 * - Force downloading, do not use cache
 *   ```sh
 *   $ xp bundle -f src/main/webapp/static
 *   ```
 *
 * This will create `bundle`.js and `bundle`.css from the given libraries and
 * place them in the given target directory.
 */
class BundleRunner {

  /** Displays success message */
  private static function success(int $bundles, float $elapsed): int {
    Console::$out->writeLinef(
      "\e[32mSuccess: %d bundle(s) created in %.3f seconds using %.2f kB memory\e[0m",
      $bundles,
      $elapsed,
      Runtime::getInstance()->peakMemoryUsage() / 1024
    );
    return 0;
  }

  /** Displays error message */
  private static function error(int $code, string $message): int {
    Console::$err->writeLinef("\e[31m*** Error: %s\e[0m", $message);
    return $code;
  }

  /** Entry point */
  public static function main(array $args): int {
    $config= 'package.json';
    $target= 'static';
    $force= false;
    for ($i= 0, $s= sizeof($args); $i < $s; $i++) {
      if ('-c' === $args[$i]) {
        $config= $args[++$i];
      } else if ('-f' === $args[$i]) {
        $force= true;
      } else {
        $target= $args[$i];
      }
    }

    if ('-' === $config) {
      $input= new StreamInput(Console::$in->stream());
    } else if (is_dir($config)) {
      $input= new FileInput($config.DIRECTORY_SEPARATOR.'package.json');
    } else if (is_file($config)) {
      $input= new FileInput($config);
    } else {
      return self::error(2, 'No configuration file found, tried '.$config);
    }

    $package= Json::read($input);
    if (!isset($package['bundles'])) {
      return self::error(1, 'No bundles found in '.$config);
    }

    $fetch= new Fetch(Environment::tempDir(), $force, [
      'cached' => function($r) { Console::write('(cached', $r ? '' : '*', ') '); },
      'update' => function($t) { Console::writef('%d%s', $t, str_repeat("\x08", strlen($t))); },
      'final'  => function($t) { Console::writef('%s%s', str_repeat(' ', strlen($t)), str_repeat("\x08", strlen($t))); },
    ]);

    $handlers= [
      'css' => new ProcessStylesheet(),
      'js'  => new ProcessJavaScript(),
      '*'   => new StoreFile($target),
    ];

    $cdn= new CDN($fetch);
    $resolve= new Resolver($fetch);
    $bundles= 0;
    $pwd= new Folder('.');

    try {
      $timer= (new Timer())->start();
      foreach ($package['bundles'] as $name => $spec) {
        $result= new Result($cdn, $handlers);
        Console::writeLine("\e[32mGenerating ", $name, " bundles\e[0m");

        // Include all dependencies
        foreach (new Dependencies($spec, $package['dependencies']) as $dependency) {
          Console::write("\e[37;1m", $dependency->library, "\e[0m@", $dependency->constraint, " => ");
          $version= $resolve->version($dependency->library, $dependency->constraint);
          Console::writeLine("\e[37;1m", $version, "\e[0m");

          $result->include($dependency, $version);
        }

        // Generate bundles
        foreach ($result->bundles() as $type => $source) {
          $bundle= with ($source, new File($target, $name.'.'.$type), function($in, $file) {
            $in->transfer($file->out());
            return $file;
          });
          $bundles++;

          Console::writeLinef('%s: %.2f kB', str_replace($pwd->getURI(), '', $bundle->getURI()), $bundle->size() / 1024);
        }
        Console::writeLine();
      }

      return self::success($bundles, $timer->elapsedTime());
    } catch (Throwable $t) {
      return self::error(8, $t->toString());
    }
  }
}