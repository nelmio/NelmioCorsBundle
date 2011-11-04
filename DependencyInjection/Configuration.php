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

        $children = $rootNode
            ->children();

            $this->addOptions(
                $children->arrayNode('defaults')->addDefaultsIfNotSet()
            )->end();

            $this->addOptions(
                $children
                    ->arrayNode('paths')
                        ->useAttributeAsKey('path')
                        ->prototype('array')
            )->end()
        ->end();

        return $treeBuilder;
    }

    protected function addOptions($node)
    {
        $node
            ->children()
                ->booleanNode('allow_credentials')->defaultFalse()->end()
                ->arrayNode('allow_origin')
                    ->beforeNormalization()
                        ->always(function($v) {
                            if ($v === '*') {
                                return array('*');
                            }
                            return $v;
                        })
                    ->end()
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('allow_headers')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('allow_methods')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('expose_headers')
                    ->prototype('scalar')->end()
                ->end()
                ->scalarNode('max_age')
                    ->defaultValue(0)
                    ->validate()
                        ->ifTrue(function ($v) {
                            return !is_numeric($v);
                        })
                        ->thenInvalid('max_age must be an integer (seconds)')
                    ->end()
                ->end()
            ->end();

        return $node;
    }
}
