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

use Nelmio\CorsBundle\Options\ProviderInterface;
use Nelmio\CorsBundle\Options\Resolver;
use Mockery as m;
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
     * @var array
     */
    protected $defaultProviderValue;

    /**
     * Return value of the extra (high priority) provider
     * @var array
     */
    protected $extraProviderValue;

    public function tearDown()
    {
        m::close();
    }

    public function testGetOptionsForPath()
    {
        $this->defaultProviderValue = array(
            'simple_value' => 'a',
            'other_simple_value' => 'b',
            'array_value' => array( 'a', 'b' ),
            'other_array_value' => array( 'c', 'd' ),
        );

        $this->extraProviderValue = array(
            'simple_value' => 'c',
            'array_value' => array( 'e' ),
            'new_value' => 'x'
        );

        self::assertEquals(
            array(
                'simple_value' => 'c',
                'other_simple_value' => 'b',
                'array_value' => array( 'e' ),
                'other_array_value' => array( 'c', 'd' ),
                'new_value' => 'x'
            ),
            $this->getResolver()->getOptions(new Request)
        );
    }

    /**
     * @return Resolver
     */
    protected function getResolver()
    {
        return new Resolver(
            array(
                $this->getDefaultProviderMock(),
                $this->getExtraProviderMock()
            )
        );
    }

    /**
     * @return m\MockInterface|ProviderInterface
     */
    protected function getDefaultProviderMock()
    {
        $mock = $this->getProviderMock();
        $mock
            ->shouldReceive('getOptions')
            ->once()
            ->andReturn($this->defaultProviderValue);

        return $mock;
    }

    /**
     * @return m\MockInterface|ProviderInterface
     */
    protected function getExtraProviderMock()
    {
        $mock = $this->getProviderMock();
        $mock
            ->shouldReceive('getOptions')
            ->once()
            ->andReturn($this->extraProviderValue);

        return $mock;
    }

    /**
     * @return m\MockInterface|ProviderInterface
     */
    protected function getProviderMock()
    {
        return m::mock('Nelmio\CorsBundle\Options\ProviderInterface');
    }
}
