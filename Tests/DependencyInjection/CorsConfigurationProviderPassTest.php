<?php
/*
 * This file is part of the NelmioCorsBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\Tests\DependencyInjection;

use Fixtures\ProviderMock;
use Nelmio\CorsBundle\DependencyInjection\Compiler\CorsConfigurationProviderPass;
use Nelmio\CorsBundle\DependencyInjection\NelmioCorsExtension;
use Nelmio\CorsBundle\Options\ConfigProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class CorsConfigurationProviderPassTest extends TestCase
{
    public function testCollectProviders(): void
    {
        $container = $this->getContainerBuilder();
        $container->compile();

        $arguments = $container->getDefinition('nelmio_cors.options_resolver')->getArguments();

        static::assertCount(4, $arguments[0] ?? []);
        static::assertSame(ConfigProvider::class, (string) $arguments[0][0]->getClass());
        static::assertSame('cors.options_provider.test3', (string) $arguments[0][1]);
        static::assertSame('cors.options_provider.test4', (string) $arguments[0][2]);
        static::assertSame('cors.options_provider.test2', (string) $arguments[0][3]);
    }

    protected function getContainerBuilder(): ContainerBuilder
    {
        $extension = new NelmioCorsExtension();
        $container = new ContainerBuilder();
        $optionProviders = [
            'cors.options_provider.test1' => (new Definition(ProviderMock::class))->setPublic(true),
            'cors.options_provider.test2' => (new Definition(ProviderMock::class))->setPublic(true)->addTag('nelmio_cors.options_provider', ['priority' => 10]),
            'cors.options_provider.test3' => (new Definition(ProviderMock::class))->setPublic(true)->addTag('nelmio_cors.options_provider', ['priority' => 5]),
            'cors.options_provider.test4' => (new Definition(ProviderMock::class))->setPublic(true)->addTag('nelmio_cors.options_provider', ['priority' => 5]),
        ];
        $container->addDefinitions($optionProviders);
        $container->addCompilerPass(new CorsConfigurationProviderPass());
        $extension->load([], $container);
        $container->getDefinition('nelmio_cors.options_resolver')->setPublic(true);

        return $container;
    }
}
