<?php namespace web\frontend\unittest\bundler;

use io\streams\MemoryInputStream;
use unittest\{Assert, Test, Values};
use util\URI;
use xp\frontend\{Resolver, Fetch, Cached};

class ResolverTest {

  /** @return iterable */
  private function matched() {
    yield [null, '2.0.0'];
    yield ['2.0.0', '2.0.0'];
    yield ['1.7.3', '1.7.3'];
    yield ['2.0.0-beta.192', '2.0.0-beta.192'];

    yield ['^1.11', '1.11.2'];
    yield ['^1.7', '1.11.2'];
    yield ['^1.7.0', '1.11.2'];
    yield ['^2.0', '2.0.0'];
    yield ['^0.9', '0.9.5'];

    yield ['~1.11', '1.11.2'];
    yield ['~1.7', '1.11.2'];
    yield ['~1.7.0', '1.7.4'];
    yield ['~2.0', '2.0.0'];
    yield ['~0.9', '0.10.0'];

    yield ['1.*', '1.11.2'];
    yield ['1.7.*', '1.7.4'];
    yield ['2.0.*', '2.0.0'];
    yield ['0.9.*', '0.9.5'];
  }

  #[Test, Values('matched')]
  public function version($constraint, $expected) {
    $r= new Resolver(new class('.', false, null) extends Fetch {
      public function get($url, $revalidate= true) {
        $json= '{
          "versions" : {
            "2.1.0-dev"      : { },
            "2.0.0"          : { },
            "2.0.0-beta.192" : { },
            "1.11.2"         : { },
            "1.10.1"         : { },
            "1.11.1"         : { },
            "1.7.0"          : { },
            "1.8.0"          : { },
            "1.8.1"          : { },
            "1.7.3"          : { },
            "1.7.4"          : { },
            "1.9.0"          : { },
            "1.7.2"          : { },
            "1.7.1"          : { },
            "1.10.0"         : { },
            "1.11.0"         : { },
            "0.10.0"         : { },
            "0.9.5"          : { }
          }
        }';

        return new Cached(new URI($url), new MemoryInputStream($json), false, ['cached' => function() { }]);
      }
    });
    Assert::equals($expected, $r->version('test', $constraint));
  }
}