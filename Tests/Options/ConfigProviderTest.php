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
    protected $defaultOptions = [
        'allow_credentials' => false,
        'allow_origin' => ['http://one.example.com'],
        'allow_headers' => false,
        'allow_methods' => ['GET'],
        'expose_headers' => [],
        'max_age' => 0,
        'hosts' => [],
    ];

    protected $pathOptions = [
        'allow_credentials' => true,
        'allow_origin' => ['http://two.example.com'],
        'allow_headers' => true,
        'allow_methods' => ['PUT', 'POST'],
        'expose_headers' => ['X-CorsTest'],
        'max_age' => 120,
        'hosts' => [],
    ];

    protected $domainMatchOptions = [
        'allow_credentials' => true,
        'allow_origin' => ['http://domainmatch.example.com'],
        'allow_headers' => true,
        'allow_methods' => ['PUT', 'POST'],
        'expose_headers' => [],
        'max_age' => 160,
        'hosts' => ['^test\.', '\.example\.org$'],
    ];

    protected $noDomainMatchOptions = [
        'allow_credentials' => true,
        'allow_origin' => ['http://nomatch.example.com'],
        'allow_headers' => true,
        'allow_methods' => ['PUT', 'POST'],
        'expose_headers' => ['X-CorsTest'],
        'max_age' => 180,
        'hosts' => ['^nomatch\.'],
    ];

    protected $originRegexOptions = [
        'allow_credentials' => true,
        'allow_origin' => ['^http://(.*)\.example\.com'],
        'origin_regex' => true,
        'allow_headers' => true,
        'allow_methods' => ['PUT', 'POST'],
        'expose_headers' => [],
        'max_age' => 0,
        'hosts' => [],
    ];

    public function testGetOptionsForPathDefault(): void
    {
        $provider = $this->getProvider();

        self::assertEquals(
            $this->defaultOptions,
            $provider->getOptions(Request::create('/default/path'))
        );
    }

    public function testGetOptionsForMappedPath(): void
    {
        $provider = $this->getProvider();

        self::assertEquals(
            $this->pathOptions,
            $provider->getOptions(Request::create('/test/abc'))
        );
    }

    public function testGetOptionsMatchingDomain(): void
    {
        $provider = $this->getProvider();

        self::assertEquals(
            $this->domainMatchOptions,
            $provider->getOptions(Request::create('/test/match', 'OPTIONS', [], [], [], ['HTTP_HOST' => 'test.example.com']))
        );
    }

    public function testGetOptionsMatchingDomain2(): void
    {
        $provider = $this->getProvider();

        self::assertEquals(
            $this->domainMatchOptions,
            $provider->getOptions(Request::create('/test/match', 'OPTIONS', [], [], [], ['HTTP_HOST' => 'foo.example.org']))
        );
    }

    public function testGetOptionsNotMatchingDomain(): void
    {
        $provider = $this->getProvider();

        self::assertEquals(
            $this->pathOptions,
            $provider->getOptions(Request::create('/test/nomatch', 'OPTIONS', [], [], [], ['HTTP_HOST' => 'example.com']))
        );
    }

    public function testGetOptionsRegexOrigin(): void
    {
        $provider = $this->getProvider();

        self::assertEquals(
            $this->originRegexOptions,
            $provider->getOptions(Request::create('/test/regex'))
        );
    }

    protected function getProvider(): ConfigProvider
    {
        return new ConfigProvider(
            [
                '^/test/regex' => $this->originRegexOptions,
                '^/test/match' => $this->domainMatchOptions,
                '^/test/nomatch' => $this->noDomainMatchOptions,
                '^/test/' => $this->pathOptions,
                '^/othertest/' => [
                    'allow_credentials' => true,
                    'allow_origin' => ['http://nope.example.com'],
                    'allow_headers' => true,
                    'allow_methods' => ['COPY'],
                    'expose_headers' => ['X-Cors-Nope'],
                    'max_age' => 42,
                ],
            ],
            $this->defaultOptions
        );
    }
}
