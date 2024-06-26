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
use Nelmio\CorsBundle\DependencyInjection\NelmioCorsExtension;

/**
 * Default CORS configuration provider.
 *
 * Uses the bundle's semantic configuration.
 * Default settings are the lowest priority one, and can be relied upon.
 *
 * @phpstan-import-type CorsCompleteOptions from ProviderInterface
 * @phpstan-import-type CorsOptionsPerPath from ProviderInterface
 */
class ConfigProvider implements ProviderInterface
{
    /**
     * @var CorsOptionsPerPath
     */
    protected $paths;
    /**
     * @var array<string, bool|array<string>|int>
     * @phpstan-var CorsCompleteOptions
     */
    protected $defaults;

    /**
     * @param CorsOptionsPerPath $paths
     * @param array<string, bool|array<string>|int> $defaults
     * @phpstan-param CorsCompleteOptions $defaults
     */
    public function __construct(array $paths, ?array $defaults = null)
    {
        $this->defaults = $defaults === null ? NelmioCorsExtension::DEFAULTS : $defaults;
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
}
