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
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class CorsListenerTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function getListener($dispatcher, array $options = array())
    {
        $mergedOptions = array_merge(
            array(
                'allow_origin' => array(),
                'allow_credentials' => false,
                'allow_headers' => array(),
                'expose_headers' => array(),
                'allow_methods' => array(),
                'max_age' => 0,
                'hosts' => array(),
                'origin_regex' => false,
                'forced_allow_origin_value' => null,
            ),
            $options
        );

        $resolver = m::mock('Nelmio\CorsBundle\Options\ResolverInterface');
        $resolver->shouldReceive('getOptions')->andReturn($mergedOptions);

        return new CorsListener($dispatcher, $resolver);
    }

    public function testPreflightedRequest()
    {
        $options = array(
            'allow_origin' => array(true),
            'allow_headers' => array('foo', 'bar'),
            'allow_methods' => array('POST', 'PUT'),
        );

        // preflight
        $req = Request::create('/foo', 'OPTIONS');
        $req->headers->set('Origin', 'http://example.com');
        $req->headers->set('Access-Control-Request-Method', 'POST');
        $req->headers->set('Access-Control-Request-Headers', 'Foo, BAR');

        $dispatcher = m::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $event = new GetResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($dispatcher, $options)->onKernelRequest($event);
        $resp = $event->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $resp);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertEquals('http://example.com', $resp->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('POST, PUT', $resp->headers->get('Access-Control-Allow-Methods'));
        $this->assertEquals('foo, bar', $resp->headers->get('Access-Control-Allow-Headers'));

        // actual request
        $req = Request::create('/foo', 'POST');
        $req->headers->set('Origin', 'http://example.com');
        $req->headers->set('Foo', 'huh');
        $req->headers->set('BAR', 'lala');

        $callback = null;
        $dispatcher = m::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $dispatcher->shouldReceive('addListener')->once()
            ->with('kernel.response', m::type('callable'), 0)
            ->andReturnUsing(function ($cb) use (&$callback) {
                $callback = $cb;
            });

        $event = new GetResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($dispatcher, $options)->onKernelRequest($event);
        $event = new FilterResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST, new Response());
        $this->getListener($dispatcher, $options)->onKernelResponse($event);
        $resp = $event->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $resp);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertEquals('http://example.com', $resp->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals(null, $resp->headers->get('Access-Control-Allow-Methods'));
        $this->assertEquals(null, $resp->headers->get('Access-Control-Allow-Headers'));
    }

    public function testPreflightedRequestLinkFirefox()
    {
        $options = array(
            'allow_origin' => array(true),
            'allow_methods' => array('LINK', 'PUT'),
        );

        // preflight
        $req = Request::create('/foo', 'OPTIONS');
        $req->headers->set('Origin', 'http://example.com');
        $req->headers->set('Access-Control-Request-Method', 'Link');

        $dispatcher = m::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $event = new GetResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($dispatcher, $options)->onKernelRequest($event);
        $resp = $event->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $resp);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertEquals('http://example.com', $resp->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('LINK, PUT, Link', $resp->headers->get('Access-Control-Allow-Methods'));
    }

    public function testPreflightedRequestWithForcedAllowOriginValue()
    {
        // allow_origin matches origin header
        // => 'Access-Control-Allow-Origin' should be equal to "forced_allow_origin_value" (i.e. '*')
        $options = array(
            'allow_origin' => array(true),
            'allow_methods' => array('GET'),
            'forced_allow_origin_value' => '*',
        );

        $req = Request::create('/foo', 'OPTIONS');
        $req->headers->set('Origin', 'http://example.com');
        $req->headers->set('Access-Control-Request-Method', 'GET');

        $dispatcher = m::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $dispatcher->shouldReceive('addListener')->once()->with('kernel.response', m::type('callable'), -1);

        $event = new GetResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($dispatcher, $options)->onKernelRequest($event);
        $event = new FilterResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST, $event->getResponse());
        $this->getListener($dispatcher, $options)->forceAccessControlAllowOriginHeader($event);
        $resp = $event->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $resp);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertEquals('*', $resp->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('GET', $resp->headers->get('Access-Control-Allow-Methods'));

        // allow_origin does not match origin header
        // => 'Access-Control-Allow-Origin' should be equal to "forced_allow_origin_value" (i.e. '*')
        $options = array(
            'allow_origin' => array(),
            'allow_methods' => array('GET'),
            'forced_allow_origin_value' => '*',
        );

        $req = Request::create('/foo', 'OPTIONS');
        $req->headers->set('Origin', 'http://example.com');
        $req->headers->set('Access-Control-Request-Method', 'GET');

        $dispatcher = m::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $dispatcher->shouldReceive('addListener')->once()->with('kernel.response', m::type('callable'), -1);

        $event = new GetResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($dispatcher, $options)->onKernelRequest($event);
        $event = new FilterResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST, $event->getResponse());
        $this->getListener($dispatcher, $options)->forceAccessControlAllowOriginHeader($event);
        $resp = $event->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $resp);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertEquals('*', $resp->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('GET', $resp->headers->get('Access-Control-Allow-Methods'));
    }

    public function testSameHostRequest()
    {
        // Request with same host as origin
        $options = array(
            'allow_origin' => array(),
            'allow_headers' => array('foo', 'bar'),
            'allow_methods' => array('POST', 'PUT'),
        );

        $req = Request::create('/foo', 'POST');
        $req->headers->set('Host', 'example.com');
        $req->headers->set('Origin', 'http://example.com');

        $dispatcher = m::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $event = new GetResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($dispatcher, $options)->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testRequestWithOriginButNo()
    {
        // Request with same host as origin
        $options = array(
            'allow_origin' => array(),
        );

        $req = Request::create('/foo', 'GET');
        $req->headers->set('Host', 'example.com');
        $req->headers->set('Origin', 'http://evil.com');

        $dispatcher = m::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $dispatcher->shouldReceive('addListener')->times(0);

        $event = new GetResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($dispatcher, $options)->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testRequestWithForcedAllowOriginValue()
    {
        // allow_origin matches origin header
        // => 'Access-Control-Allow-Origin' should be equal to "forced_allow_origin_value" (i.e. 'http://example.com http://huh-lala.foobar')
        $options = array(
            'allow_origin' => array('http://example.com'),
            'allow_methods' => array('GET'),
            'forced_allow_origin_value' => 'http://example.com http://huh-lala.foobar',
        );

        $req = Request::create('/foo', 'GET');
        $req->headers->set('Origin', 'http://example.com');

        $dispatcher = m::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $dispatcher->shouldReceive('addListener')->twice()->with('kernel.response', m::type('callable'), m::type('integer'));

        $event = new GetResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($dispatcher, $options)->onKernelRequest($event);
        $event = new FilterResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST, new Response());
        $this->getListener($dispatcher, $options)->onKernelResponse($event);
        $this->getListener($dispatcher, $options)->forceAccessControlAllowOriginHeader($event);
        $resp = $event->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $resp);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertEquals('http://example.com http://huh-lala.foobar', $resp->headers->get('Access-Control-Allow-Origin'));

        // request without "Origin" header
        // => 'Access-Control-Allow-Origin' should be equal to "forced_allow_origin_value" (i.e. '*')
        $options = array(
            'forced_allow_origin_value' => '*',
        );

        $req = Request::create('/foo', 'GET');

        $dispatcher = m::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $dispatcher->shouldReceive('addListener')->once()->with('kernel.response', m::type('callable'), -1);

        $event = new GetResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($dispatcher, $options)->onKernelRequest($event);
        $event = new FilterResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST, new Response());
        $this->getListener($dispatcher, $options)->forceAccessControlAllowOriginHeader($event);
        $resp = $event->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $resp);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertEquals('*', $resp->headers->get('Access-Control-Allow-Origin'));
    }
}
