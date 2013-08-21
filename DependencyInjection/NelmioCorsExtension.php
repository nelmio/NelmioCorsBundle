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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class NelmioCorsExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (!$config['paths']) {
            return;
        }

        $defaults = array_merge(
            array(
                'allow_origin' => array(),
                'allow_credentials' => false,
                'allow_headers' => array(),
                'expose_headers' => array(),
                'allow_methods' => array(),
                'max_age' => 0,
                'hosts' => array(),
            ),
            $config['defaults']
        );

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
            $opts['allow_methods'] = array_map('strtoupper', $opts['allow_methods']);

            $config['paths'][$path] = $opts;
        }

        $container->setParameter('nelmio_cors.defaults', $defaults);
        $container->setParameter('nelmio_cors.map', $config['paths']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
