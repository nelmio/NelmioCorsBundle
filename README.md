# NelmioCorsBundle

## About

The NelmioCorsBundle allows you to send [Cross-Origin Resource Sharing](http://enable-cors.org/)
headers with ACL-style per-URL configuration.

If you want to have a global overview of CORS workflow, you can browse
this [image](http://www.html5rocks.com/static/images/cors_server_flowchart.png).

## Features

* Handles CORS preflight OPTIONS requests
* Adds CORS headers to your responses
* Configured at the PHP/application level. This is convenient but it also means that any request serving static files and not going through Symfony will not have the CORS headers added, so if you need to serve CORS for static files you probably should rather configure these headers in your web server

## Installation

An official [Symfony Flex](https://symfony.com/doc/current/setup/flex.html) recipe
is available for this bundle.
To automatically install and configure it run:

```bash
composer req cors
```

You're done!

Alternatively, if you don't use Symfony Flex, require the `nelmio/cors-bundle`
package in your `composer.json` and update your dependencies.

    $ composer require nelmio/cors-bundle

Add the NelmioCorsBundle to your application's kernel:

```php
    public function registerBundles()
    {
        $bundles = [
            // ...
            new Nelmio\CorsBundle\NelmioCorsBundle(),
            // ...
        ];
        // ...
    }
```

## Configuration

Symfony Flex generates a default configuration in `config/packages/nelmio_cors.yaml`.

The `defaults` are the default values applied to all the `paths` that match,
unless overridden in a specific URL configuration. If you want them to apply
to everything, you must define a path with `^/`.

This example config contains all the possible config values with their default
values shown in the `defaults` key. In paths, you see that we allow CORS
requests from any origin on `/api/`. One custom header and some HTTP methods
are defined as allowed as well. Preflight requests can be cached for 3600
seconds.

```yaml
    nelmio_cors:
        defaults:
            allow_credentials: false
            allow_origin: []
            allow_headers: []
            allow_methods: []
            expose_headers: []
            max_age: 0
            hosts: []
            origin_regex: false
            forced_allow_origin_value: ~
            skip_same_as_origin: true
        paths:
            '^/api/':
                allow_origin: ['*']
                allow_headers: ['X-Custom-Auth']
                allow_methods: ['POST', 'PUT', 'GET', 'DELETE']
                max_age: 3600
            '^/':
                origin_regex: true
                allow_origin: ['^http://localhost:[0-9]+']
                allow_headers: ['X-Custom-Auth']
                allow_methods: ['POST', 'PUT', 'GET', 'DELETE']
                max_age: 3600
                hosts: ['^api\.']
```

`allow_origin` and `allow_headers` can be set to `*` to accept any value, the
allowed methods however have to be explicitly listed. `paths` must contain at least one item.

`expose_headers` can be set to `*` to accept any value as long as `allow_credentials` is `false`
[as per the specification](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Expose-Headers).

If `origin_regex` is set, `allow_origin` must be a list of regular expressions matching
allowed origins. Remember to use `^` and `$` to clearly define the boundaries of the regex.

By default, the `Access-Control-Allow-Origin` response header value is 
the `Origin` request header value (if it matches the rules you've defined with `allow_origin`),
so it should be fine for most of use cases. If it's not, you can override this behavior 
by setting the exact value you want using `forced_allow_origin_value`.

Be aware that even if you set `forced_allow_origin_value` to `*`, if you also set `allow_origin` to `http://example.com`,
only this specific domain will be allowed to access your resources.

> **Note:** If you allow POST methods and have 
> [HTTP method overriding](http://symfony.com/doc/current/reference/configuration/framework.html#http-method-override)
> enabled in the framework, it will enable the API users to perform PUT and DELETE 
> requests as well.

## Cookbook

### How to ignore preflight requests on New Relic?

On specific architectures with a mostly authenticated API, preflight request can represent a huge part of the traffic.

In such cases, you may not need to monitor on New Relic this traffic which is by the way categorized automatically as
`unknown` by New Relic.

A request listener can be written to ignore preflight requests:
```php
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class PreflightIgnoreOnNewRelicListener
{
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!extension_loaded('newrelic')) {
            return;
        }

        if ('OPTIONS' === $event->getRequest()->getMethod()) {
            newrelic_ignore_transaction();
        }
    }
}
```

Register this listener, and voilà!

## License

Released under the MIT License, see LICENSE.
