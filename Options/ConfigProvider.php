<?php
/*
 * This file is part of the NelmioCorsBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\CorsBundle\Options;

use Symfony\Component\HttpFoundation\Request;

/**
 * Default CORS configuration provider.
 *
 * Uses the bundle's semantic configuration.
 * Default settings are the lowest priority one, and can be relied upon.
 */
class ConfigProvider implements ProviderInterface
{
    protected $paths;
    protected $defaults;

    public function __construct(array $paths, array $defaults = [])
    {
        $this->defaults = $this->normalizeOptions($defaults);

        foreach ($paths as $path => $options) {
            $this->paths[$path] = $this->normalizeOptions($options);
        }

        $this->paths = $paths;
    }

    public function getOptions(Request $request): array
    {
        $uri = $request->getPathInfo() ?: '/';
        foreach ($this->paths as $pathRegexp => $options) {
            if (preg_match('{'.$pathRegexp.'}i', $uri)) {
                $options = array_merge($this->defaults, $options);

                // skip if the host is not matching
                if (count($options['hosts']) > 0) {
                    foreach ($options['hosts'] as $hostRegexp) {
                        if (preg_match('{'.$hostRegexp.'}i', $request->getHost())) {
                            return $options;
                        }
                    }

                    continue;
                }

                return $options;
            }
        }

        return $this->defaults;
    }

    private function normalizeOptions(array $options): array
    {
        foreach (['expose_headers', 'allow_origin', 'allow_headers', 'allow_methods', 'hosts'] as $key) {
            if (isset($options[$key]) && is_array($options[$key]) === false) {
                $options[$key] = [$options[$key]];
            }
        }

        if (
            isset($options['allow_credentials']) && isset($options['expose_headers']) &&
            $options['allow_credentials'] && in_array('*', $options['expose_headers'], true)
        ) {
            throw new \UnexpectedValueException('nelmio_cors expose_headers cannot contain a wildcard (*) when allow_credentials is enabled.');
        }

        if (isset($options['allow_origin']) && in_array('*', $options['allow_origin'])) {
            $options['allow_origin'] = true;
        }

        if (isset($options['allow_headers']) && in_array('*', $options['allow_headers'])) {
            $options['allow_headers'] = true;
        } else {
            $options['allow_headers'] = array_map('strtolower', $options['allow_headers']);
        }

        if (isset($options['allow_methods'])) {
            $options['allow_methods'] = array_map('strtoupper', $options['allow_methods']);
        }

        return $options;
    }
}
