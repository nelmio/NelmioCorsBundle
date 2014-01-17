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

use Nelmio\CorsBundle\Options\ConfigProvider;
use Symfony\Component\HttpFoundation\Request;

class ConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    protected $defaultOptions = array(
        'allow_credentials' => false,
        'allow_origin' => array( 'http://one.example.com' ),
        'allow_headers' => false,
        'allow_methods' => array( 'GET' ),
        'expose_headers' => array(),
        'max_age' => 0
    );

    protected $pathOptions = array(
        'allow_credentials' => true,
        'allow_origin' => array( 'http://two.example.com' ),
        'allow_headers' => true,
        'allow_methods' => array( 'PUT', 'POST' ),
        'expose_headers' => array( 'X-CorsTest' ),
        'max_age' => 120
    );

    public function testGetOptionsForPathDefault()
    {
        $provider = $this->getProvider();

        self::assertEquals(
            $this->defaultOptions,
            $provider->getOptions(Request::create('/default/path'))
        );
    }

    public function testGetOptionsForMappedPath()
    {
        $provider = $this->getProvider();

        self::assertEquals(
            $this->pathOptions,
            $provider->getOptions(Request::create('/test/abc'))
        );
    }

    /**
     * @return ConfigProvider
     */
    protected function getProvider()
    {
        return new ConfigProvider(
            array(
                '^/test/' => $this->pathOptions,
                '^/othertest/' => array(
                    'allow_credentials' => true,
                    'allow_origin' => array( 'http://nope.example.com' ),
                    'allow_headers' => true,
                    'allow_methods' => array( 'COPY' ),
                    'expose_headers' => array( 'X-Cors-Nope' ),
                    'max_age' => 42
                )
            ),
            $this->defaultOptions
        );
    }
}
