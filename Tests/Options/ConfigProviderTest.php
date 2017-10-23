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
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ConfigProviderTest extends TestCase
{
    protected $defaultOptions = array(
        'allow_credentials' => false,
        'allow_origin' => array('http://one.example.com'),
        'allow_headers' => false,
        'allow_methods' => array('GET'),
        'expose_headers' => array(),
        'max_age' => 0,
        'hosts' => array(),
    );

    protected $pathOptions = array(
        'allow_credentials' => true,
        'allow_origin' => array('http://two.example.com'),
        'allow_headers' => true,
        'allow_methods' => array('PUT', 'POST'),
        'expose_headers' => array('X-CorsTest'),
        'max_age' => 120,
        'hosts' => array(),
    );

    protected $domainMatchOptions = array(
        'allow_credentials' => true,
        'allow_origin' => array('http://domainmatch.example.com'),
        'allow_headers' => true,
        'allow_methods' => array('PUT', 'POST'),
        'expose_headers' => array(),
        'max_age' => 160,
        'hosts' => array('^test\.', '\.example\.org$'),
    );

    protected $noDomainMatchOptions = array(
        'allow_credentials' => true,
        'allow_origin' => array('http://nomatch.example.com'),
        'allow_headers' => true,
        'allow_methods' => array('PUT', 'POST'),
        'expose_headers' => array('X-CorsTest'),
        'max_age' => 180,
        'hosts' => array('^nomatch\.'),
    );

    protected $originRegexOptions = array(
        'allow_credentials' => true,
        'allow_origin' => array('^http://(.*)\.example\.com'),
        'origin_regex' => true,
        'allow_headers' => true,
        'allow_methods' => array('PUT', 'POST'),
        'expose_headers' => array(),
        'max_age' => 0,
        'hosts' => array(),
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

    public function testGetOptionsMatchingDomain()
    {
        $provider = $this->getProvider();

        self::assertEquals(
            $this->domainMatchOptions,
            $provider->getOptions(Request::create('/test/match', 'OPTIONS', array(), array(), array(), array('HTTP_HOST' => 'test.example.com')))
        );
    }

    public function testGetOptionsMatchingDomain2()
    {
        $provider = $this->getProvider();

        self::assertEquals(
            $this->domainMatchOptions,
            $provider->getOptions(Request::create('/test/match', 'OPTIONS', array(), array(), array(), array('HTTP_HOST' => 'foo.example.org')))
        );
    }

    public function testGetOptionsNotMatchingDomain()
    {
        $provider = $this->getProvider();

        self::assertEquals(
            $this->pathOptions,
            $provider->getOptions(Request::create('/test/nomatch', 'OPTIONS', array(), array(), array(), array('HTTP_HOST' => 'example.com')))
        );
    }

    public function testGetOptionsRegexOrigin()
    {
        $provider = $this->getProvider();

        self::assertEquals(
            $this->originRegexOptions,
            $provider->getOptions(Request::create('/test/regex'))
        );
    }

    /**
     * @return ConfigProvider
     */
    protected function getProvider()
    {
        return new ConfigProvider(
            array(
                '^/test/regex' => $this->originRegexOptions,
                '^/test/match' => $this->domainMatchOptions,
                '^/test/nomatch' => $this->noDomainMatchOptions,
                '^/test/' => $this->pathOptions,
                '^/othertest/' => array(
                    'allow_credentials' => true,
                    'allow_origin' => array('http://nope.example.com'),
                    'allow_headers' => true,
                    'allow_methods' => array('COPY'),
                    'expose_headers' => array('X-Cors-Nope'),
                    'max_age' => 42
                )
            ),
            $this->defaultOptions
        );
    }
}
