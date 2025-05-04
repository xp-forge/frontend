<?php namespace web\frontend;

use util\Random;

/**
 * Security headers
 *
 * @see   https://www.youtube.com/watch?v=mr230uotw-Y
 * @see   https://scotthelme.co.uk/hardening-your-http-response-headers/
 * @see   https://webhint.io/docs/user-guide/hints/hint-x-content-type-options/
 * @test  web.frontend.unittest.SecurityTest
 */
class Security {
  private $headers= [
    'X-Content-Type-Options'  => 'nosniff',
    'X-Frame-Options'         => 'DENY',
    'Referrer-Policy'         => 'no-referrer-when-downgrade',
  ];

  /** Sets frame options */
  public function framing(string $value): self {
    $this->headers['X-Frame-Options']= $value;
    return $this;
  }

  /** Sets referrer policy */
  public function referrers(string $value): self {
    $this->headers['Referrer-Policy']= $value;
    return $this;
  }

  /**
   * Sets content security policy
   * 
   * @param  string|[:string|string[]] $policy
   * @param  bool $reportOnly whether to report only (true) or to enforce (false)
   * @return self
   * @see    https://content-security-policy.com/
   */
  public function csp($policy, bool $reportOnly= false): self {
    $name= $reportOnly ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';

    if (is_array($policy)) {
      $header= '';
      foreach ($policy as $source => $value) {
        $header.= '; '.$source.' '.strtr(is_array($value) ? implode(' ', $value) : $value, '"', "'");
      }
      $this->headers[$name]= substr($header, 2);
    } else {
      $this->headers[$name]= $policy;
    }

    return $this;
  }

  /**
   * Yields security headers, replacing placeholders with random values.
   * Returns the replacement values map, if any.
   *
   * @return iterable
   */
  public function headers() {
    $variables= [];
    $random= null;
    foreach ($this->headers as $name => $value) {
      yield $name => preg_replace_callback(
        '/\{\{\s?([^}]+)\s?\}\}/',
        function($m) use(&$variables) {
          return $variables[$m[1]]??= bin2hex(($random??= new Random())->bytes(16));
        },
        $value
      );
    }
    return $variables;
  }

  /**
   * Passes security headers and context to view and returns it.
   *
   * @param  web.frontend.View
   * @return web.frontend.View
   */
  public function apply($view) {
    $headers= $this->headers();
    foreach ($headers as $name => $value) {
      $view->header($name, $value);
    }
    return $view->with($headers->getReturn());
  }
}