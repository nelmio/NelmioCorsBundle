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

use Symfony\Component\Config\Definition\Builder\BooleanNodeDefinition;
use Symfony\Component\Config\Definition\Builder\IntegerNodeDefinition;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\VariableNodeDefinition;
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
                    ->append($this->getAllowCredentials())
                    ->append($this->getAllowOrigin())
                    ->append($this->getAllowHeaders())
                    ->append($this->getAllowMethods())
                    ->append($this->getExposeHeaders())
                    ->append($this->getMaxAge())
                    ->append($this->getHosts())
                    ->append($this->getOriginRegex())
                    ->append($this->getForcedAllowOriginValue())
                    ->append($this->getSkipSameAsOrigin())
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
                        ->append($this->getSkipSameAsOrigin())
                    ->end()
                ->end()
            ;

        return $treeBuilder;
    }

    private function getSkipSameAsOrigin(): BooleanNodeDefinition
    {
        $node = new BooleanNodeDefinition('skip_same_as_origin');
        $node->defaultTrue();

        return $node;
    }

    private function getAllowCredentials(): BooleanNodeDefinition
    {
        $node = new BooleanNodeDefinition('allow_credentials');
        $node->defaultFalse();

        return $node;
    }

    private function getAllowOrigin(): VariableNodeDefinition
    {
        $node = new VariableNodeDefinition('allow_origin');

        $node->defaultValue([]);

        return $node;
    }

    private function getAllowHeaders(): VariableNodeDefinition
    {
        $node = new VariableNodeDefinition('allow_headers');

        $node->defaultValue([]);

        return $node;
    }

    private function getAllowMethods(): VariableNodeDefinition
    {
        $node = new VariableNodeDefinition('allow_methods');

        $node->defaultValue([]);

        return $node;
    }

    private function getExposeHeaders(): VariableNodeDefinition
    {
        $node = new VariableNodeDefinition('expose_headers');

        $node->defaultValue([]);

        return $node;
    }

    private function getMaxAge(): ScalarNodeDefinition
    {
        $node = new IntegerNodeDefinition('max_age');

        $node
            ->defaultValue(0)
            ->min(0)
            ->info('The value of the Access-Control-Max-Age header (in seconds).')
            ->end()
        ;

        return $node;
    }

    private function getHosts(): VariableNodeDefinition
    {
        $node = new VariableNodeDefinition('hosts');

        $node->defaultValue([]);

        return $node;
    }

    private function getOriginRegex(): BooleanNodeDefinition
    {
        $node = new BooleanNodeDefinition('origin_regex');
        $node->defaultFalse();

        return $node;
    }

    private function getForcedAllowOriginValue(): ScalarNodeDefinition
    {
        $node = new ScalarNodeDefinition('forced_allow_origin_value');
        $node->defaultNull();

        return $node;
    }
}
