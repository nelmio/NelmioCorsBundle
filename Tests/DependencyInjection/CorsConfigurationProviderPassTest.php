<?php
/**
 * File containing the CorsConfigurationProviderPassTest class.
 *
 * @copyright Copyright (C) 2013 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 */
namespace Nelmio\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Nelmio\CorsBundle\DependencyInjection\Compiler\CorsConfigurationProviderPass;
use Symfony\Component\DependencyInjection\Reference;

class CorsConfigurationProviderPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new CorsConfigurationProviderPass());
    }

    public function testCollectProviders()
    {
        $configurationResolver = new Definition();
        $this->setDefinition('nelmio_cors.options_resolver', $configurationResolver);

        $configurationProvider = new Definition();
        $configurationProvider->addTag('nelmio_cors.options_provider');
        $this->setDefinition('cors.options_provider.test1', $configurationProvider);

        $configurationProvider = new Definition();
        $configurationProvider->addTag('nelmio_cors.options_provider', array('priority' => 10));
        $this->setDefinition('cors.options_provider.test2', $configurationProvider);

        $configurationProvider = new Definition();
        $configurationProvider->addTag('nelmio_cors.options_provider', array('priority' => 5));
        $this->setDefinition('cors.options_provider.test3', $configurationProvider);

        $configurationProvider = new Definition();
        $configurationProvider->addTag('nelmio_cors.options_provider', array('priority' => 5));
        $this->setDefinition('cors.options_provider.test4', $configurationProvider);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'nelmio_cors.options_resolver',
            0,
            array(
                new Reference('cors.options_provider.test1'),
                new Reference('cors.options_provider.test3'),
                new Reference('cors.options_provider.test4'),
                new Reference('cors.options_provider.test2')
            )
        );
    }
}
