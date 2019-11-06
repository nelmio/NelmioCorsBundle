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

use Mockery as m;
use Nelmio\CorsBundle\EventListener\CorsListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class CorsListenerTest extends TestCase
{
    public function tearDown(): void
    {
        m::close();
    }

    public function getListener(array $options = []): CorsListener
    {
        $mergedOptions = array_merge(
            [
                'allow_origin' => [],
                'allow_credentials' => false,
                'allow_headers' => [],
                'expose_headers' => [],
                'allow_methods' => [],
                'max_age' => 0,
                'hosts' => [],
                'origin_regex' => false,
                'forced_allow_origin_value' => null,
            ],
            $options
        );

        $resolver = m::mock('Nelmio\CorsBundle\Options\ResolverInterface');
        $resolver->shouldReceive('getOptions')->andReturn($mergedOptions);

        return new CorsListener($resolver);
    }

    public function testPreflightedRequest(): void
    {
        $options = [
            'allow_origin' => [true],
            'allow_headers' => ['foo', 'bar'],
            'allow_methods' => ['POST', 'PUT'],
        ];

        // preflight
        $req = Request::create('/foo', 'OPTIONS');
        $req->headers->set('Origin', 'http://example.com');
        $req->headers->set('Access-Control-Request-Method', 'POST');
        $req->headers->set('Access-Control-Request-Headers', 'Foo, BAR');

        $dispatcher = m::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $event = new RequestEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($options)->onKernelRequest($event);
        $resp = $event->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $resp);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertEquals('http://example.com', $resp->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('POST, PUT', $resp->headers->get('Access-Control-Allow-Methods'));
        $this->assertEquals('foo, bar', $resp->headers->get('Access-Control-Allow-Headers'));
        $this->assertEquals(['Origin'], $resp->getVary());

        // actual request
        $req = Request::create('/foo', 'POST');
        $req->headers->set('Origin', 'http://example.com');
        $req->headers->set('Foo', 'huh');
        $req->headers->set('BAR', 'lala');

        $event = new RequestEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($options)->onKernelRequest($event);
        $event = new ResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST, new Response());
        $this->getListener($options)->onKernelResponse($event);
        $resp = $event->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $resp);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertEquals('http://example.com', $resp->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals(null, $resp->headers->get('Access-Control-Allow-Methods'));
        $this->assertEquals(null, $resp->headers->get('Access-Control-Allow-Headers'));
    }

    public function testPreflightedRequestLinkFirefox(): void
    {
        $options = [
            'allow_origin' => [true],
            'allow_methods' => ['LINK', 'PUT'],
        ];

        // preflight
        $req = Request::create('/foo', 'OPTIONS');
        $req->headers->set('Origin', 'http://example.com');
        $req->headers->set('Access-Control-Request-Method', 'Link');

        $event = new RequestEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($options)->onKernelRequest($event);
        $resp = $event->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $resp);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertEquals('http://example.com', $resp->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('LINK, PUT, Link', $resp->headers->get('Access-Control-Allow-Methods'));
        $this->assertEquals(['Origin'], $resp->getVary());
    }

    public function testPreflightedRequestWithForcedAllowOriginValue(): void
    {
        // allow_origin matches origin header
        // => 'Access-Control-Allow-Origin' should be equal to "forced_allow_origin_value" (i.e. '*')
        $options = [
            'allow_origin' => [true],
            'allow_methods' => ['GET'],
            'forced_allow_origin_value' => '*',
        ];

        $req = Request::create('/foo', 'OPTIONS');
        $req->headers->set('Origin', 'http://example.com');
        $req->headers->set('Access-Control-Request-Method', 'GET');

        $event = new RequestEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($options)->onKernelRequest($event);
        $event = new ResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST, $event->getResponse());
        $this->getListener($options)->onKernelResponse($event);
        $resp = $event->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $resp);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertEquals('*', $resp->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('GET', $resp->headers->get('Access-Control-Allow-Methods'));
        $this->assertEquals(['Origin'], $resp->getVary());

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

        $event = new RequestEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($options)->onKernelRequest($event);
        $event = new ResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST, $event->getResponse());
        $this->getListener($options)->onKernelResponse($event);
        $resp = $event->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $resp);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertEquals('*', $resp->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('GET', $resp->headers->get('Access-Control-Allow-Methods'));
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

        $event = new RequestEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($options)->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testPreflightedRequestWithOriginButNo()
    {
        $options = [
            'allow_origin' => [],
            'allow_methods' => ['POST', 'PUT'],
        ];

        $req = Request::create('/foo', 'OPTIONS');
        $req->headers->set('Host', 'example.com');
        $req->headers->set('Origin', 'http://evil.com');
        $req->headers->set('Access-Control-Request-Method', 'POST');

        $event = new RequestEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($options)->onKernelRequest($event);
        $resp = $event->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $resp);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertNull($resp->headers->get('Access-Control-Allow-Origin'));
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

        $event = new RequestEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($options)->onKernelRequest($event);

        $this->assertNull($event->getResponse());
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

        $event = new RequestEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($options)->onKernelRequest($event);
        $event = new ResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST, new Response());
        $this->getListener($options)->onKernelResponse($event);
        $resp = $event->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $resp);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertEquals('http://example.com http://huh-lala.foobar', $resp->headers->get('Access-Control-Allow-Origin'));

        // request without "Origin" header
        // => 'Access-Control-Allow-Origin' should be equal to "forced_allow_origin_value" (i.e. '*')
        $options = [
            'forced_allow_origin_value' => '*',
        ];

        $req = Request::create('/foo', 'GET');

        $event = new RequestEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($options)->onKernelRequest($event);
        $event = new ResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST, new Response());
        $this->getListener($options)->onKernelResponse($event);
        $resp = $event->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $resp);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertEquals('*', $resp->headers->get('Access-Control-Allow-Origin'));
    }
}
