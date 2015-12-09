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
