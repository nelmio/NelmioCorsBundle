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

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\BooleanNodeDefinition;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('nelmio_cors');

        if (method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC for symfony/config < 4.2
            $rootNode = $treeBuilder->root('nelmio_cors');
        }

        $rootNode
            ->children()
                ->arrayNode('defaults')
                    ->addDefaultsIfNotSet()
                    ->append($this->getAllowCredentials(true))
                    ->append($this->getAllowOrigin())
                    ->append($this->getAllowHeaders())
                    ->append($this->getAllowMethods())
                    ->append($this->getAllowPrivateNetwork())
                    ->append($this->getExposeHeaders())
                    ->append($this->getMaxAge())
                    ->append($this->getHosts())
                    ->append($this->getOriginRegex(true))
                    ->append($this->getForcedAllowOriginValue())
                    ->append($this->getSkipSameAsOrigin(true))
                ->end()

                ->arrayNode('paths')
                    ->useAttributeAsKey('path')
                    ->normalizeKeys(false)
                    ->prototype('array')
                        ->append($this->getAllowCredentials())
                        ->append($this->getAllowOrigin())
                        ->append($this->getAllowHeaders())
                        ->append($this->getAllowMethods())
                        ->append($this->getAllowPrivateNetwork())
                        ->append($this->getExposeHeaders())
                        ->append($this->getMaxAge())
                        ->append($this->getHosts())
                        ->append($this->getOriginRegex())
                        ->append($this->getForcedAllowOriginValue())
                        ->append($this->getSkipSameAsOrigin())
                    ->end()
                ->end()
            ;

        return $treeBuilder;
    }

    private function getSkipSameAsOrigin(bool $withDefaultValue = false): BooleanNodeDefinition
    {
        $node = new BooleanNodeDefinition('skip_same_as_origin');

        if ($withDefaultValue) {
            $node->defaultTrue();
        }

        return $node;
    }

    private function getAllowCredentials(bool $withDefaultValue = false): BooleanNodeDefinition
    {
        $node = new BooleanNodeDefinition('allow_credentials');

        if ($withDefaultValue) {
            $node->defaultFalse();
        }

        return $node;
    }

    private function getAllowOrigin(): ArrayNodeDefinition
    {
        $node = new ArrayNodeDefinition('allow_origin');

        $node
            ->beforeNormalization()
                ->always(function ($v) {
                    if ($v === '*') {
                        return ['*'];
                    }

                    return $v;
                })
            ->end()
            ->prototype('scalar')->end()
        ;

        return $node;
    }

    private function getAllowHeaders(): ArrayNodeDefinition
    {
        $node = new ArrayNodeDefinition('allow_headers');

        $node
            ->beforeNormalization()
                ->always(function ($v) {
                    if ($v === '*') {
                        return ['*'];
                    }

                    return $v;
                })
            ->end()
            ->prototype('scalar')->end();

        return $node;
    }

    private function getAllowMethods(): ArrayNodeDefinition
    {
        $node = new ArrayNodeDefinition('allow_methods');

        $node->prototype('scalar')->end();

        return $node;
    }

    private function getAllowPrivateNetwork(): BooleanNodeDefinition
    {
        $node = new BooleanNodeDefinition('allow_private_network');
        $node->defaultFalse();

        return $node;
    }

    private function getExposeHeaders(): ArrayNodeDefinition
    {
        $node = new ArrayNodeDefinition('expose_headers');

        $node
            ->beforeNormalization()
                ->always(function ($v) {
                    if ($v === '*') {
                        return ['*'];
                    }

                    return $v;
                })
            ->end()
            ->prototype('scalar')->end();

        return $node;
    }

    private function getMaxAge(): ScalarNodeDefinition
    {
        $node = new ScalarNodeDefinition('max_age');

        $node
            ->defaultValue(0)
            ->validate()
                ->ifTrue(function ($v) {
                    return !is_numeric($v);
                })
                ->thenInvalid('max_age must be an integer (seconds)')
            ->end()
        ;

        return $node;
    }

    private function getHosts(): ArrayNodeDefinition
    {
        $node = new ArrayNodeDefinition('hosts');

        $node->prototype('scalar')->end();

        return $node;
    }

    private function getOriginRegex(bool $withDefaultValue = false): BooleanNodeDefinition
    {
        $node = new BooleanNodeDefinition('origin_regex');

        if ($withDefaultValue) {
            $node->defaultFalse();
        }

        return $node;
    }

    private function getForcedAllowOriginValue(): ScalarNodeDefinition
    {
        $node = new ScalarNodeDefinition('forced_allow_origin_value');
        $node->defaultNull();

        return $node;
    }
}
