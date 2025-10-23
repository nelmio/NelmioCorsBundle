<?php

/*
 * This file is part of the NelmioCorsBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\CorsBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class NelmioCorsExtension extends Extension
{
    public const DEFAULTS = [
        'allow_origin' => [],
        'allow_credentials' => false,
        'allow_headers' => [],
        'allow_private_network' => false,
        'expose_headers' => [],
        'allow_methods' => [],
        'max_age' => 0,
        'hosts' => [],
        'origin_regex' => false,
        'skip_same_as_origin' => true,
    ];

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $defaults = array_merge(self::DEFAULTS, $config['defaults']);

        if ($defaults['allow_credentials'] && in_array('*', $defaults['expose_headers'], true)) {
            throw new \UnexpectedValueException('nelmio_cors expose_headers cannot contain a wildcard (*) when allow_credentials is enabled.');
        }

        // normalize array('*') to true
        if (in_array('*', $defaults['allow_origin'])) {
            $defaults['allow_origin'] = true;
        }
        if (in_array('*', $defaults['allow_headers'])) {
            $defaults['allow_headers'] = true;
        } else {
            $defaults['allow_headers'] = array_map('strtolower', $defaults['allow_headers']);
        }
        $defaults['allow_methods'] = array_map('strtoupper', $defaults['allow_methods']);

        if ($config['paths']) {
            foreach ($config['paths'] as $path => $opts) {
                $opts = array_filter($opts);
                if (isset($opts['allow_origin']) && in_array('*', $opts['allow_origin'])) {
                    $opts['allow_origin'] = true;
                }
                if (isset($opts['allow_headers']) && in_array('*', $opts['allow_headers'])) {
                    $opts['allow_headers'] = true;
                } elseif (isset($opts['allow_headers'])) {
                    $opts['allow_headers'] = array_map('strtolower', $opts['allow_headers']);
                }
                if (isset($opts['allow_methods'])) {
                    $opts['allow_methods'] = array_map('strtoupper', $opts['allow_methods']);
                }

                $config['paths'][$path] = $opts;
            }
        }

        $container->setParameter('nelmio_cors.map', $config['paths']);
        $container->setParameter('nelmio_cors.defaults', $defaults);

        $locator = new FileLocator(__DIR__.'/../Resources/config');
        if (Kernel::VERSION_ID >= 70400) {
            $loader = new Loader\PhpFileLoader($container, $locator);
            $loader->load('services.php');
        } else {
            $loader = new Loader\XmlFileLoader($container, $locator);
            $loader->load('services.xml');
        }
    }
}
