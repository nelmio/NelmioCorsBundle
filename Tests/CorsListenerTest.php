<?php

/*
 * This file is part of the NelmioCorsBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\CorsBundle\Tests;

use Nelmio\CorsBundle\EventListener\CorsListener;
use Nelmio\CorsBundle\Options\ProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @phpstan-import-type CorsOptions from ProviderInterface
 */
class CorsListenerTest extends TestCase
{
    /**
     * @param CorsOptions $options
     */
    public function getListener(array $options = []): CorsListener
    {
        $mergedOptions = array_merge(
            [
                'allow_origin' => [],
                'allow_credentials' => false,
                'allow_headers' => [],
                'expose_headers' => [],
                'allow_methods' => [],
                'allow_private_network' => false,
                'max_age' => 0,
                'hosts' => [],
                'origin_regex' => false,
                'forced_allow_origin_value' => null,
                'skip_same_as_origin' => true,
            ],
            $options
        );

        $resolver = $this->getMockBuilder('Nelmio\CorsBundle\Options\ResolverInterface')->getMock();
        $resolver->expects($this->once())
            ->method('getOptions')
            ->willReturn($mergedOptions);

        return new CorsListener($resolver);
    }

    public function testPreflightedRequest(): void
    {
        $options = [
            'allow_origin' => true,
            'allow_headers' => ['foo', 'bar'],
            'allow_methods' => ['POST', 'PUT'],
        ];

        // preflight
        $req = Request::create('/foo', 'OPTIONS');
        $req->headers->set('Origin', 'http://example.com');
        $req->headers->set('Access-Control-Request-Method', 'POST');
        $req->headers->set('Access-Control-Request-Headers', 'Foo, BAR');

        $event = new RequestEvent($this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')->getMock(), $req, HttpKernelInterface::MAIN_REQUEST);
        $this->getListener($options)->onKernelRequest($event);
        $resp = $event->getResponse();
        self::assertInstanceOf('Symfony\Component\HttpFoundation\Response', $resp);
        self::assertEquals(200, $resp->getStatusCode());
        self::assertEquals('http://example.com', $resp->headers->get('Access-Control-Allow-Origin'));
        self::assertEquals('POST, PUT', $resp->headers->get('Access-Control-Allow-Methods'));
        self::assertEquals('foo, bar', $resp->headers->get('Access-Control-Allow-Headers'));
        self::assertEquals(['Origin'], $resp->getVary());

        // actual request
        $req = Request::create('/foo', 'POST');
        $req->headers->set('Origin', 'http://example.com');
        $req->headers->set('Foo', 'huh');
        $req->headers->set('BAR', 'lala');

        $event = new RequestEvent($this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')->getMock(), $req, HttpKernelInterface::MAIN_REQUEST);
        $this->getListener($options)->onKernelRequest($event);
        $event = new ResponseEvent($this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')->getMock(), $req, HttpKernelInterface::MAIN_REQUEST, new Response());
        $this->getListener($options)->onKernelResponse($event);
        $resp = $event->getResponse();
        self::assertEquals(200, $resp->getStatusCode());
        self::assertEquals('http://example.com', $resp->headers->get('Access-Control-Allow-Origin'));
        self::assertEquals(null, $resp->headers->get('Access-Control-Allow-Methods'));
        self::assertEquals(null, $resp->headers->get('Access-Control-Allow-Headers'));
    }

    public function testPreflightedRequestLinkFirefox(): void
    {
        $options = [
            'allow_origin' => true,
            'allow_methods' => ['LINK', 'PUT'],
        ];

        // preflight
        $req = Request::create('/foo', 'OPTIONS');
        $req->headers->set('Origin', 'http://example.com');
        $req->headers->set('Access-Control-Request-Method', 'Link');

        $event = new RequestEvent($this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')->getMock(), $req, HttpKernelInterface::MAIN_REQUEST);
        $this->getListener($options)->onKernelRequest($event);
        $resp = $event->getResponse();
        self::assertInstanceOf('Symfony\Component\HttpFoundation\Response', $resp);
        self::assertEquals(200, $resp->getStatusCode());
        self::assertEquals('http://example.com', $resp->headers->get('Access-Control-Allow-Origin'));
        self::assertEquals('LINK, PUT, Link', $resp->headers->get('Access-Control-Allow-Methods'));
        self::assertEquals(['Origin'], $resp->getVary());
    }

    public function testPreflightedRequestWithForcedAllowOriginValue(): void
    {
        // allow_origin matches origin header
        // => 'Access-Control-Allow-Origin' should be equal to "forced_allow_origin_value" (i.e. '*')
        $options = [
            'allow_origin' => true,
            'allow_methods' => ['GET'],
            'forced_allow_origin_value' => '*',
        ];

        $req = Request::create('/foo', 'OPTIONS');
        $req->headers->set('Origin', 'http://example.com');
        $req->headers->set('Access-Control-Request-Method', 'GET');

        $event = new RequestEvent($this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')->getMock(), $req, HttpKernelInterface::MAIN_REQUEST);
        $this->getListener($options)->onKernelRequest($event);
        self::assertInstanceOf('Symfony\Component\HttpFoundation\Response', $event->getResponse());
        $event = new ResponseEvent($this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')->getMock(), $req, HttpKernelInterface::MAIN_REQUEST, $event->getResponse());
        $this->getListener($options)->onKernelResponse($event);
        $resp = $event->getResponse();
        self::assertEquals(200, $resp->getStatusCode());
        self::assertEquals('*', $resp->headers->get('Access-Control-Allow-Origin'));
        self::assertEquals('GET', $resp->headers->get('Access-Control-Allow-Methods'));
        self::assertEquals(['Origin'], $resp->getVary());

        // allow_origin does not match origin header
        // => 'Access-Control-Allow-Origin' should be equal to "forced_allow_origin_value" (i.e. '*')
        $options = [
            'allow_origin' => [],
            'allow_methods' => ['GET'],
            'forced_allow_origin_value' => '*',
        ];

        $req = Request::create('/foo', 'OPTIONS');
        $req->headers->set('Origin', 'http://example.com');
        $req->headers->set('Access-Control-Request-Method', 'GET');

        $event = new RequestEvent($this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')->getMock(), $req, HttpKernelInterface::MAIN_REQUEST);
        $this->getListener($options)->onKernelRequest($event);
        self::assertInstanceOf('Symfony\Component\HttpFoundation\Response', $event->getResponse());
        $event = new ResponseEvent($this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')->getMock(), $req, HttpKernelInterface::MAIN_REQUEST, $event->getResponse());
        $this->getListener($options)->onKernelResponse($event);
        $resp = $event->getResponse();
        self::assertEquals(200, $resp->getStatusCode());
        self::assertEquals('*', $resp->headers->get('Access-Control-Allow-Origin'));
        self::assertEquals('GET', $resp->headers->get('Access-Control-Allow-Methods'));
    }

    public function testSameHostRequest(): void
    {
        // Request with same host as origin
        $options = [
            'allow_origin' => [],
            'allow_headers' => ['foo', 'bar'],
            'allow_methods' => ['POST', 'PUT'],
        ];

        $req = Request::create('/foo', 'POST');
        $req->headers->set('Host', 'example.com');
        $req->headers->set('Origin', 'http://example.com');

        $event = new RequestEvent($this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')->getMock(), $req, HttpKernelInterface::MAIN_REQUEST);
        $this->getListener($options)->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testPreflightedRequestWithOriginButNo(): void
    {
        $options = [
            'allow_origin' => [],
            'allow_methods' => ['POST', 'PUT'],
        ];

        $req = Request::create('/foo', 'OPTIONS');
        $req->headers->set('Host', 'example.com');
        $req->headers->set('Origin', 'http://evil.com');
        $req->headers->set('Access-Control-Request-Method', 'POST');

        $event = new RequestEvent($this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')->getMock(), $req, HttpKernelInterface::MAIN_REQUEST);
        $this->getListener($options)->onKernelRequest($event);
        $resp = $event->getResponse();
        self::assertInstanceOf('Symfony\Component\HttpFoundation\Response', $resp);
        self::assertEquals(200, $resp->getStatusCode());
        self::assertNull($resp->headers->get('Access-Control-Allow-Origin'));
    }

    public function testRequestWithOriginButNo(): void
    {
        // Request with same host as origin
        $options = [
            'allow_origin' => [],
        ];

        $req = Request::create('/foo', 'GET');
        $req->headers->set('Host', 'example.com');
        $req->headers->set('Origin', 'http://evil.com');

        $event = new RequestEvent($this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')->getMock(), $req, HttpKernelInterface::MAIN_REQUEST);
        $this->getListener($options)->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testRequestWithForcedAllowOriginValue(): void
    {
        // allow_origin matches origin header
        // => 'Access-Control-Allow-Origin' should be equal to "forced_allow_origin_value" (i.e. 'http://example.com http://huh-lala.foobar')
        $options = [
            'allow_origin' => ['http://example.com'],
            'allow_methods' => ['GET'],
            'forced_allow_origin_value' => 'http://example.com http://huh-lala.foobar',
        ];

        $req = Request::create('/foo', 'GET');
        $req->headers->set('Origin', 'http://example.com');

        $event = new RequestEvent($this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')->getMock(), $req, HttpKernelInterface::MAIN_REQUEST);
        $this->getListener($options)->onKernelRequest($event);
        $event = new ResponseEvent($this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')->getMock(), $req, HttpKernelInterface::MAIN_REQUEST, new Response());
        $this->getListener($options)->onKernelResponse($event);
        $resp = $event->getResponse();
        self::assertEquals(200, $resp->getStatusCode());
        self::assertEquals('http://example.com http://huh-lala.foobar', $resp->headers->get('Access-Control-Allow-Origin'));

        // request without "Origin" header
        // => 'Access-Control-Allow-Origin' should be equal to "forced_allow_origin_value" (i.e. '*')
        $options = [
            'forced_allow_origin_value' => '*',
        ];

        $req = Request::create('/foo', 'GET');

        $event = new RequestEvent($this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')->getMock(), $req, HttpKernelInterface::MAIN_REQUEST);
        $this->getListener($options)->onKernelRequest($event);
        $event = new ResponseEvent($this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')->getMock(), $req, HttpKernelInterface::MAIN_REQUEST, new Response());
        $this->getListener($options)->onKernelResponse($event);
        $resp = $event->getResponse();
        self::assertEquals(200, $resp->getStatusCode());
        self::assertEquals('*', $resp->headers->get('Access-Control-Allow-Origin'));
    }

    /**
     * @param bool        $option
     * @param string|null $header
     * @param string|null $expectedHeader
     * @param int         $expectedStatus
     */
    private function testPreflightedRequestWithPrivateNetworkAccess($option, $header, $expectedHeader, $expectedStatus): void
    {
        $options = [
            'allow_origin' => true,
            'allow_headers' => ['foo', 'bar'],
            'allow_methods' => ['POST', 'PUT'],
            'allow_private_network' => $option,
        ];

        // preflight
        $req = Request::create('/foo', 'OPTIONS');
        $req->headers->set('Origin', 'http://example.com');
        $req->headers->set('Access-Control-Request-Method', 'POST');
        $req->headers->set('Access-Control-Request-Headers', 'Foo, BAR');
        if ($header) {
            $req->headers->set('Access-Control-Request-Private-Network', $header);
        }

        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')->getMock();
        $event = new RequestEvent($this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')->getMock(), $req, HttpKernelInterface::MAIN_REQUEST);
        $this->getListener($options)->onKernelRequest($event);
        $resp = $event->getResponse();
        self::assertInstanceOf('Symfony\Component\HttpFoundation\Response', $resp);
        self::assertEquals($expectedStatus, $resp->getStatusCode());
        self::assertEquals('http://example.com', $resp->headers->get('Access-Control-Allow-Origin'));
        self::assertEquals('POST, PUT', $resp->headers->get('Access-Control-Allow-Methods'));
        self::assertEquals('foo, bar', $resp->headers->get('Access-Control-Allow-Headers'));
        self::assertEquals($expectedHeader, $resp->headers->get('Access-Control-Allow-Private-Network'));
        self::assertEquals(['Origin'], $resp->getVary());

        // actual request
        $req = Request::create('/foo', 'POST');
        $req->headers->set('Origin', 'http://example.com');
        $req->headers->set('Foo', 'huh');
        $req->headers->set('BAR', 'lala');

        $event = new RequestEvent($this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')->getMock(), $req, HttpKernelInterface::MAIN_REQUEST);
        $this->getListener($options)->onKernelRequest($event);
        $event = new ResponseEvent($this->getMockBuilder('Symfony\Component\HttpKernel\HttpKernelInterface')->getMock(), $req, HttpKernelInterface::MAIN_REQUEST, new Response());
        $this->getListener($options)->onKernelResponse($event);
        $resp = $event->getResponse();
        self::assertEquals(200, $resp->getStatusCode());
        self::assertEquals('http://example.com', $resp->headers->get('Access-Control-Allow-Origin'));
        self::assertEquals(null, $resp->headers->get('Access-Control-Allow-Methods'));
        self::assertEquals(null, $resp->headers->get('Access-Control-Allow-Headers'));
        self::assertEquals(null, $resp->headers->get('Access-Control-Allow-Private-Network'));
    }

    public function testPreflightedRequestWithPrivateNetworkAccessAllowedAndProvided(): void
    {
        $this->testPreflightedRequestWithPrivateNetworkAccess(true, 'true', 'true', 200);
    }

    public function testPreflightedRequestWithPrivateNetworkAccessAllowedButNotProvided(): void
    {
        $this->testPreflightedRequestWithPrivateNetworkAccess(true, null, null, 200);
    }

    public function testPreflightedRequestWithPrivateNetworkAccessForbiddenButProvided(): void
    {
        $this->testPreflightedRequestWithPrivateNetworkAccess(false, 'true', null, 400);
    }

    public function testPreflightedRequestWithPrivateNetworkAccessForbiddenAndNotProvided(): void
    {
        $this->testPreflightedRequestWithPrivateNetworkAccess(false, null, null, 200);
    }
}
