<?php

/*
 * This file is part of the NelmioCorsBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Nelmio\CorsBundle\EventListener\CacheableResponseVaryListener;
use Nelmio\CorsBundle\EventListener\CorsListener;
use Nelmio\CorsBundle\Options\ConfigProvider;
use Nelmio\CorsBundle\Options\Resolver;

return function (ContainerConfigurator $container): void {
    $parameters = $container->parameters();
    $parameters
        ->set('nelmio_cors.cors_listener.class', CorsListener::class)
        ->set('nelmio_cors.options_resolver.class', Resolver::class)
        ->set('nelmio_cors.options_provider.config.class', ConfigProvider::class)
    ;

    $services = $container->services();
    $services
        ->set('nelmio_cors.cors_listener', param('nelmio_cors.cors_listener.class'))
        ->args([
            service('nelmio_cors.options_resolver'),
        ])
        ->tag('kernel.event_listener', [
            'event' => 'kernel.request',
            'method' => 'onKernelRequest',
            'priority' => 250,
        ])
        ->tag('kernel.event_listener', [
            'event' => 'kernel.response',
            'method' => 'onKernelResponse',
            'priority' => 0,
        ])
    ;

    $services
        ->set('nelmio_cors.options_resolver', param('nelmio_cors.options_resolver.class'))
    ;

    $services
        ->set('nelmio_cors.options_provider.config', param('nelmio_cors.options_provider.config.class'))
        ->args([
            param('nelmio_cors.map'),
            param('nelmio_cors.defaults'),
        ])
        ->tag('nelmio_cors.options_provider', [
            'priority' => -1,
        ])
    ;

    $services
        ->set('nelmio_cors.cacheable_response_vary_listener', CacheableResponseVaryListener::class)
        ->tag('kernel.event_listener', [
            'event' => 'kernel.response',
            'method' => 'onResponse',
            'priority' => -10,
        ])
    ;
};
