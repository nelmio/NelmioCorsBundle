# NelmioCorsBundle

## About

The NelmioCorsBundle allows you to send [Cross-Origin Resource Sharing](http://enable-cors.org/)
headers with ACL-style per-url configuration.

If you want to have have a global overview of CORS workflow, you can  browse
this [image](http://www.html5rocks.com/static/images/cors_server_flowchart.png).

## Features

* Handles CORS pre-flight OPTIONS requests
* Adds CORS headers to your responses

## Configuration

The `defaults` are the default values applied to all the `paths` that match,
unless overriden in a specific URL configuration. If you want them to apply
to everything, you must define a path with `^/`.

This example config contains all the possible config values with their default
values shown in the `defaults` key. In paths, you see that we allow CORS
requests from any origin on `/api/`. One custom header and some HTTP methods
are defined as allowed as well. Preflight requests can be cached for 3600
seconds.

    nelmio_cors:
        defaults:
            allow_credentials: false
            allow_origin: []
            allow_headers: []
            allow_methods: []
            expose_headers: []
            max_age: 0
            hosts: []
        paths:
            '^/api/':
                allow_origin: ['*']
                allow_headers: ['X-Custom-Auth']
                allow_methods: ['POST', 'PUT', 'GET', 'DELETE']
                max_age: 3600
            '^/':
                allow_origin: ['*']
                allow_headers: ['X-Custom-Auth']
                allow_methods: ['POST', 'PUT', 'GET', 'DELETE']
                max_age: 3600
                hosts: ['^api\.']

`allow_origin` and `allow_headers` can be set to `*` to accept any value, the
allowed methods however have to be explicitly listed. `paths` must contain at least one item.

> **Note:** If you allow POST methods and have 
> [http method overriding](http://symfony.com/doc/current/reference/configuration/framework.html#http-method-override)
> enabled in the framework, it will enable the API users to perform PUT and DELETE 
> requests as well.

## Installation (Symfony 2.2+)

Require the `nelmio/cors-bundle` package in your composer.json and update your dependencies.

    $ composer require nelmio/cors-bundle:~1.0

Add the NelmioCorsBundle to your application's kernel:

    public function registerBundles()
    {
        $bundles = array(
            ...
            new Nelmio\CorsBundle\NelmioCorsBundle(),
            ...
        );
        ...
    }

## Installation (Symfony 2.0)

Put the NelmioCorsBundle into the `vendor/bundles/Nelmio` directory:

    $ git clone git://github.com/nelmio/NelmioCorsBundle.git vendor/bundles/Nelmio/CorsBundle

Register the `Nelmio` namespace in your project's autoload script (app/autoload.php):

    $loader->registerNamespaces(array(
        'Nelmio'                        => __DIR__.'/../vendor/bundles',
    ));

Add the NelmioCorsBundle to your application's kernel:

    public function registerBundles()
    {
        $bundles = array(
            ...
            new Nelmio\CorsBundle\NelmioCorsBundle(),
            ...
        );
        ...
    }

## License

Released under the MIT License, see LICENSE.
