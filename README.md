# NelmioCorsBundle

## About

The NelmioCorsBundle allows you to send [Cross-Origin Resource Sharing](http://enable-cors.org/)
headers with ACL-style per-URL configuration.

## Features

* Handles CORS preflight OPTIONS requests
* Adds CORS headers to your responses
* Configured at the PHP/application level. This is convenient but it also means
  that any request serving static files and not going through Symfony will not
  have the CORS headers added, so if you need to serve CORS for static files you
  probably should rather configure these headers in your web server

## Installation

Require the `nelmio/cors-bundle` package in your composer.json and update your dependencies:

```bash
composer require nelmio/cors-bundle
```

The bundle should be automatically enabled by [Symfony Flex][1]. If you don't use
Flex, you'll need to enable it manually as explained [in the docs][2].

## Usage

See [the documentation][2] for usage instructions.

## License

Released under the MIT License, see LICENSE.

[1]: https://symfony.com/doc/current/setup/flex.html
[2]: https://symfony.com/bundles/NelmioCorsBundle/current/index.html
