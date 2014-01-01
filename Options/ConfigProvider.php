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

    public function __construct(array $paths, array $defaults = array())
    {
        $this->defaults = $defaults;
        $this->paths = $paths;
    }

    public function getOptions(Request $request)
    {
        $uri = $request->getPathInfo() ?: '/';
        foreach ($this->paths as $pathRegexp => $options) {
            if (preg_match('{'.$pathRegexp.'}i', $uri)) {
                return array_merge($this->defaults, $options);
            }
        }
        return $this->defaults;
    }
}
