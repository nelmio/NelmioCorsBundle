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
 * CORS options resolver.
 *
 * Uses Cors providers to resolve options for an HTTP request
 */
class Resolver implements ResolverInterface
{
    /**
     * CORS configuration providers, indexed by numerical priority
     * @var ProviderInterface[][]
     */
    private $providers;

    /**
     * @param $providers ProviderInterface[]
     */
    public function __construct(array $providers = array())
    {
        $this->providers = $providers;
    }

    /**
     * Resolves the options for $request based on {@see $providers} data
     *
     * @param Request $request
     *
     * @return array CORS options
     */
    public function getOptions(Request $request)
    {
        $options = array();
        foreach ($this->providers as $provider) {
            $options = array_merge($options, $provider->getOptions($request));
        }

        return $options;
    }
}
