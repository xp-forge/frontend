<?php namespace xp\frontend;

use io\{File, Folder, Path};
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
 *     "vendor": {
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
 * - Create an asset manifest
 *   ```sh
 *   $ xp bundle -m manifest.json src/main/webapp/static
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
 * This will create `vendor`.js and `vendor`.css (as well as gzipped versions)
 * from the given libraries and place them in the given target directory.
 */
class BundleRunner {

  /** Displays success message */
  private static function success(int $bundles, bool $manifest, float $elapsed): int {
    Console::$out->writeLinef(
      "\e[32mBundle operations: %d bundle(s)%s created in %.3f seconds using %.2f kB memory\e[0m",
      $bundles,
      $manifest ? ' + manifest' : '',
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
    $manifest= null;
    $force= false;
    $target= 'static';
    for ($i= 0, $s= sizeof($args); $i < $s; $i++) {
      if ('-c' === $args[$i]) {
        $config= $args[++$i];
      } else if ('-m' === $args[$i]) {
        $manifest= new Manifest($args[++$i]);
      } else if ('-f' === $args[$i]) {
        $force= true;
      } else {
        $target= $args[$i];
      }
    }

    if ('-' === $config) {
      $input= new StreamInput(Console::$in->stream());
      $relative= new Path('.');
    } else if (is_dir($config)) {
      $input= new FileInput($config.DIRECTORY_SEPARATOR.'package.json');
      $relative= new Path($config);
    } else if (is_file($config)) {
      $input= new FileInput($config);
      $relative= new Path(dirname($config));
    } else {
      return self::error(2, 'No configuration file found, tried '.$config);
    }

    $package= Json::read($input);
    if (!isset($package['bundles'])) {
      return self::error(1, 'No bundles found in '.$config);
    }

    $files= $manifest ? new WithFingerprints($target, $manifest) : new UsingFilenames($target);
    $fetch= new Fetch(Environment::tempDir(), $force);
    $progress= [
      'start'  => function($r) { Console::writef("\r\e[0K> \e[34m%s\e[0m ", $r); },
      'cached' => function($r) { Console::write('(cached', $r ? '' : '*', ') '); },
      'update' => function($t) { Console::writef('%d%s', $t, str_repeat("\x08", strlen($t))); },
      'final'  => function($t) { Console::writef('%s%s', str_repeat(' ', strlen($t)), str_repeat("\x08", strlen($t))); },
    ];
    $handlers= [
      'fonts' => new ProcessFonts($files),
      'css'   => new ProcessStylesheet($files),
      'js'    => new ProcessJavaScript(),
      '*'    => new StoreFile($files),
    ];

    try {
      $timer= (new Timer())->start();
      $bundles= [];

      // Resolve all versions
      Console::writeLine("\e[32mResolving package versions\e[0m");
      $resolve= new Resolver($fetch);
      foreach ($package['bundles'] as $name => $spec) {
        foreach ($spec as $source => $names) {
          $sources= array_map('trim', is_array($names) ? $names : explode('|', $names));

          if ($constraint= $package['dependencies'][$source] ?? null) {
            Console::writef("  - Resolving \e[32mnpm/%s\e[0m (\e[33m%s\e[0m => ", $source, $constraint);
            $version= $resolve->version($source, $constraint);
            Console::writeLine("\e[33m", $version, "\e[0m)");
            $bundles[$name][]= new LibraryDependency($source, $version, $sources);
          } else if (preg_match('/^https?:\/\//', $source)) {
            Console::writeLinef("  - Resolving \e[32m%s\e[0m (\e[33mremote\e[0m)", $source);
            $bundles[$name][]= new RemoteDependency($source, $sources);
          } else if (preg_match('/^fonts?:\/\//', $source)) {
            Console::writeLinef("  - Resolving \e[32m%s\e[0m (\e[33mfonts\e[0m)", implode(' & ', $sources));
            $bundles[$name][]= new FontsDependency($source, $sources);
          } else {
            Console::writeLinef("  - Resolving \e[32m%s\e[0m (\e[33mlocal %s\e[0m)", $source, $relative);
            $bundles[$name][]= new LocalDependency($relative->resolve($source), $sources);
          }
        }
      }

      // Download dependencies
      $cdn= new CDN($fetch, null, $progress);
      $cwd= new Folder('.');
      foreach ($bundles as $name => $dependencies) {
        Console::writeLinef("\e[32mGenerating %s bundle\e[0m (dependencies: %d)", $name, sizeof($dependencies));
        $result= new Result($cdn, $handlers);
        foreach ($dependencies as $dependency) {
          $result->include($dependency);
        }

        foreach ($result->sources() as $type => $source) {
          $bundle= new Bundle($target, $files->resolve($name, $type, $source->hash));
          with ($source, $bundle, function($in, $target) {
            $in->transfer($target);
          });

          foreach ($bundle->files() as $file) {
            $path= str_replace($cwd->getURI(), '', realpath($file->getURI()));
            Console::writeLinef("\r\e[0K> %s: \e[33m%.2f kB\e[0m", $path, $file->size() / 1024);
          }
        }
      }

      // Clean up previous versions of our bundles
      if ($manifest) {
        Console::writeLinef("\e[32mCleaning up previous versions\e[0m");
        foreach ($manifest->removed() as $remove) {
          $bundle= new Bundle($target, $remove);
          $bundle->close();
          foreach ($bundle->files() as $file) {
            $path= str_replace($cwd->getURI(), '', realpath($file->getURI()));
            $file->exists() && $file->unlink();
            Console::writeLinef("\r\e[0K> %s: \e[33m(deleted)\e[0m", $path);
          }
        }
        $manifest->save();
      }

      return self::success(sizeof($bundles), isset($manifest), $timer->elapsedTime());
    } catch (Throwable $t) {
      return self::error(8, $t->toString());
    }
  }
}