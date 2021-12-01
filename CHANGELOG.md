Newer changelog entries can be found in the [GitHub Releases](https://github.com/nelmio/NelmioCorsBundle/releases)

### 2.2.0 (2021-12-01)

  * Added support for Symfony 6

### 2.1.1 (2021-04-20)

  * Fixed response for unauthorized headers containing a reflected XSS (https://github.com/nelmio/NelmioCorsBundle/pull/163)

### 2.1.0 (2020-07-22)

  * Added `Vary: Origin` header to cacheable responses to make sure proxies cache them correctly

### 2.0.1 (2019-11-15)

  * Reverted CorsListener priority change as it was interfering with normal operations. The priority is back at 250.

### 2.0.0 (2019-11-12)

  * BC Break: Downgraded CorsListener priority from 250 to 28, this should not affect anyone but could be a source in case of strange bugs
  * BC Break: Removed support for Symfony <4.3
  * BC Break: Removed support for PHP <7.1
  * Added support for Symfony 5
  * Added support for configuration via env vars
  * Changed the code to avoid mutating the EventDispatcher at runtime
  * Changed the code to avoid returning `Access-Control-Allow-Origin: null` headers to mark blocked requests

### 1.5.6 (2019-06-17)

  * Fixed preflight request handler hijacking regular non-CORS OPTIONS requests.

### 1.5.5 (2019-02-27)

  * Compatibility with Symfony 4.1
  * Fixed preflight responses to always include `Origin` in the `Vary` HTTP header

### 1.5.4 (2017-12-11)

  * Compatibility with Symfony 4

### 1.5.3 (2017-04-24)

  * Fixed regression in 1.5.2

### 1.5.2 (2017-04-21)

  * Fixed bundle initialization in case paths is empty

### 1.5.1 (2017-01-22)

  * Fixed `forced_allow_origin_value` to always set the header regardless of CORS, so that requests can properly be cached even if they are not always accessed via CORS

### 1.5.0 (2016-12-30)

  * Added an `forced_allow_origin_value` option to force the value that is returned, in case you cache responses and can not have the allowed origin automatically set to the Origin header
  * Fixed `Access-Control-Allow-Headers` being sent even when it was empty
  * Fixed listener priority down to 250 (This **may be BREAKING** depending on what you do with your own listeners, but should be fine in most cases, just watch out).

### 1.4.1 (2015-12-09)

  * Fixed requirements to allow Symfony3

### 1.4.0 (2015-01-13)

  * Added an `origin_regex` option to allow defining origins based on regular expressions

### 1.3.3 (2014-12-10)

  * Fixed a security regression in 1.3.2 that allowed GET requests to be executed from any domain

### 1.3.2 (2014-09-18)

  * Removed 403 responses on non-OPTIONS requests that have an invalid origin header

### 1.3.1 (2014-07-21)

  * Fixed path key normalization to allow dashes in paths
  * Fixed HTTP method case folding to support clients that send non-uppercased method names

### 1.3.0 (2014-02-06)

  * Added support for host-based configuration of the bundle

### 1.2.0 (2013-10-29)

  * Bumped symfony dependency to 2.1.0+
  * Fixed invalid trigger of the CORS check when the Origin header is present on same-host requests
  * Fixed fatal error when `allow_methods` was not configured for a given path

### 1.1.1 (2013-08-14)

  * Fixed issue when `allow_origin` is set to `*` and `allow_credentials` to `true`.

### 1.1.0 (2013-07-29)

  * Added ability to set a wildcard on accept_headers

### 1.0.0 (2013-01-07)

  * Initial release
