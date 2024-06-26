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
use Nelmio\CorsBundle\Options\ProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @phpstan-import-type CorsOptions from ProviderInterface
 * @phpstan-import-type CorsCompleteOptions from ProviderInterface
 */
class ConfigProviderTest extends TestCase
{
    /**
     * @phpstan-var CorsCompleteOptions
     */
    protected $defaultOptions = [
        'allow_credentials' => false,
        'allow_origin' => ['http://one.example.com'],
        'allow_headers' => false,
        'allow_private_network' => false,
        'allow_methods' => ['GET'],
        'expose_headers' => [],
        'max_age' => 0,
        'hosts' => [],
        'origin_regex' => false,
        'skip_same_as_origin' => true,
    ];

    /**
     * @phpstan-var CorsOptions
     */
    protected $pathOptions = [
        'allow_credentials' => true,
        'allow_origin' => ['http://two.example.com'],
        'allow_headers' => true,
        'allow_private_network' => false,
        'allow_methods' => ['PUT', 'POST'],
        'expose_headers' => ['X-CorsTest'],
        'max_age' => 120,
        'hosts' => [],
    ];

    /**
     * @phpstan-var CorsOptions
     */
    protected $domainMatchOptions = [
        'allow_credentials' => true,
        'allow_origin' => ['http://domainmatch.example.com'],
        'allow_headers' => true,
        'allow_private_network' => false,
        'allow_methods' => ['PUT', 'POST'],
        'expose_headers' => [],
        'max_age' => 160,
        'hosts' => ['^test\.', '\.example\.org$'],
    ];

    /**
     * @phpstan-var CorsOptions
     */
    protected $noDomainMatchOptions = [
        'allow_credentials' => true,
        'allow_origin' => ['http://nomatch.example.com'],
        'allow_headers' => true,
        'allow_private_network' => false,
        'allow_methods' => ['PUT', 'POST'],
        'expose_headers' => ['X-CorsTest'],
        'max_age' => 180,
        'hosts' => ['^nomatch\.'],
    ];

    /**
     * @phpstan-var CorsOptions
     */
    protected $originRegexOptions = [
        'allow_credentials' => true,
        'allow_origin' => ['^http://(.*)\.example\.com'],
        'origin_regex' => true,
        'allow_headers' => true,
        'allow_private_network' => false,
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
            array_merge($this->defaultOptions, $this->pathOptions),
            $provider->getOptions(Request::create('/test/abc'))
        );
    }

    public function testGetOptionsMatchingDomain(): void
    {
        $provider = $this->getProvider();

        self::assertEquals(
            array_merge($this->defaultOptions, $this->domainMatchOptions),
            $provider->getOptions(Request::create('/test/match', 'OPTIONS', [], [], [], ['HTTP_HOST' => 'test.example.com']))
        );
    }

    public function testGetOptionsMatchingDomain2(): void
    {
        $provider = $this->getProvider();

        self::assertEquals(
            array_merge($this->defaultOptions, $this->domainMatchOptions),
            $provider->getOptions(Request::create('/test/match', 'OPTIONS', [], [], [], ['HTTP_HOST' => 'foo.example.org']))
        );
    }

    public function testGetOptionsNotMatchingDomain(): void
    {
        $provider = $this->getProvider();

        self::assertEquals(
            array_merge($this->defaultOptions, $this->pathOptions),
            $provider->getOptions(Request::create('/test/nomatch', 'OPTIONS', [], [], [], ['HTTP_HOST' => 'example.com']))
        );
    }

    public function testGetOptionsRegexOrigin(): void
    {
        $provider = $this->getProvider();

        self::assertEquals(
            array_merge($this->defaultOptions, $this->originRegexOptions),
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
                    'allow_private_network' => false,
                    'allow_methods' => ['COPY'],
                    'expose_headers' => ['X-Cors-Nope'],
                    'max_age' => 42,
                ],
            ],
            $this->defaultOptions
        );
    }
}
