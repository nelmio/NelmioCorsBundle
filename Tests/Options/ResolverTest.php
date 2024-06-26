<?php
/*
 * This file is part of the NelmioCorsBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Nelmio\CorsBundle\Tests\Options;

use PHPUnit\Framework\MockObject\MockObject;
use Nelmio\CorsBundle\Options\ProviderInterface;
use Nelmio\CorsBundle\Options\Resolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ResolverTest extends TestCase
{
    /** @var Resolver */
    protected $resolver;

    /** @var ProviderInterface */
    protected $defaultProviderMock;

    /** @var ProviderInterface */
    protected $extraProviderMock;

    /**
     * Return value of the default (low priority) provider
     * @var array<string, string|string[]>
     */
    protected $defaultProviderValue;

    /**
     * Return value of the extra (high priority) provider
     * @var array<string, string|string[]>
     */
    protected $extraProviderValue;

    public function testGetOptionsForPath(): void
    {
        $this->defaultProviderValue = [
            'simple_value' => 'a',
            'other_simple_value' => 'b',
            'array_value' => ['a', 'b'],
            'other_array_value' => ['c', 'd'],
        ];

        $this->extraProviderValue = [
            'simple_value' => 'c',
            'array_value' => ['e'],
            'new_value' => 'x',
        ];

        self::assertEquals(
            [
                'simple_value' => 'c',
                'other_simple_value' => 'b',
                'array_value' => ['e'],
                'other_array_value' => ['c', 'd'],
                'new_value' => 'x',
            ],
            $this->getResolver()->getOptions(new Request())
        );
    }

    protected function getResolver(): Resolver
    {
        return new Resolver(
            [
                $this->getDefaultProviderMock(),
                $this->getExtraProviderMock(),
            ]
        );
    }

    /**
     * @return ProviderInterface&MockObject
     */
    protected function getDefaultProviderMock(): ProviderInterface
    {
        $mock = $this->getProviderMock();
        $mock->expects($this->once())
            ->method('getOptions')
            ->willReturn($this->defaultProviderValue);

        return $mock;
    }

    /**
     * @return ProviderInterface&MockObject
     */
    protected function getExtraProviderMock(): ProviderInterface
    {
        $mock = $this->getProviderMock();
        $mock->expects($this->once())
            ->method('getOptions')
            ->willReturn($this->extraProviderValue);

        return $mock;
    }

    /**
     * @return ProviderInterface&MockObject
     */
    protected function getProviderMock(): ProviderInterface
    {
        return $this->getMockBuilder('Nelmio\CorsBundle\Options\ProviderInterface')->getMock();
    }
}
