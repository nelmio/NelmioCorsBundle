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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\BooleanNodeDefinition;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('nelmio_cors');

        $rootNode
            ->children()
                ->arrayNode('defaults')
                    ->addDefaultsIfNotSet()
                    ->append($this->getAllowCredentials())
                    ->append($this->getAllowOrigin())
                    ->append($this->getAllowHeaders())
                    ->append($this->getAllowMethods())
                    ->append($this->getExposeHeaders())
                    ->append($this->getMaxAge())
                    ->append($this->getHosts())
                    ->append($this->getOriginRegex())
                    ->append($this->getForcedAllowOriginValue())
                ->end()

                ->arrayNode('paths')
                    ->useAttributeAsKey('path')
                    ->normalizeKeys(false)
                    ->prototype('array')
                        ->append($this->getAllowCredentials())
                        ->append($this->getAllowOrigin())
                        ->append($this->getAllowHeaders())
                        ->append($this->getAllowMethods())
                        ->append($this->getExposeHeaders())
                        ->append($this->getMaxAge())
                        ->append($this->getHosts())
                        ->append($this->getOriginRegex())
                        ->append($this->getForcedAllowOriginValue())
                    ->end()
                ->end()
            ;

        return $treeBuilder;
    }

    private function getAllowCredentials()
    {
        $node = new BooleanNodeDefinition('allow_credentials');
        $node->defaultFalse();

        return $node;
    }

    private function getAllowOrigin()
    {
        $node = new ArrayNodeDefinition('allow_origin');

        $node
            ->beforeNormalization()
                ->always(function ($v) {
                    if ($v === '*') {
                        return array('*');
                    }

                    return $v;
                })
            ->end()
            ->prototype('scalar')->end()
        ;

        return $node;
    }

    private function getAllowHeaders()
    {
        $node = new ArrayNodeDefinition('allow_headers');

        $node
            ->beforeNormalization()
                ->always(function ($v) {
                    if ($v === '*') {
                        return array('*');
                    }

                    return $v;
                })
            ->end()
            ->prototype('scalar')->end();

        return $node;
    }

    private function getAllowMethods()
    {
        $node = new ArrayNodeDefinition('allow_methods');

        $node->prototype('scalar')->end();

        return $node;
    }

    private function getExposeHeaders()
    {
        $node = new ArrayNodeDefinition('expose_headers');

        $node->prototype('scalar')->end();

        return $node;
    }

    private function getMaxAge()
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

    private function getHosts()
    {
        $node = new ArrayNodeDefinition('hosts');

        $node->prototype('scalar')->end();

        return $node;
    }

    private function getOriginRegex()
    {
        $node = new BooleanNodeDefinition('origin_regex');
        $node->defaultFalse();

        return $node;
    }

    private function getForcedAllowOriginValue()
    {
        $node = new ScalarNodeDefinition('forced_allow_origin_value');
        $node->defaultNull();

        return $node;
    }
}
