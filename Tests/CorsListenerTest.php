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

class CorsListenerTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function getListener($paths = array(), $defaults = array(), $dispatcher)
    {
        $defaults = array_merge(
            array(
                'allow_origin' => array(),
                'allow_credentials' => false,
                'allow_headers' => array(),
                'expose_headers' => array(),
                'allow_methods' => array(),
                'max_age' => 0,
                'hosts' => array(),
            ),
            $defaults
        );

        return new CorsListener($dispatcher, $paths, $defaults);
    }

    public function testPreflightedRequest()
    {
        $config = array('/foo' => array(
            'allow_origin' => array(true),
            'allow_headers' => array('foo', 'bar'),
            'allow_methods' => array('POST', 'PUT'),
        ));

        // preflight
        $req = Request::create('/foo', 'OPTIONS');
        $req->headers->set('Origin', 'http://example.com');
        $req->headers->set('Access-Control-Request-Method', 'POST');
        $req->headers->set('Access-Control-Request-Headers', 'Foo, BAR');

        $dispatcher = m::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $event = new GetResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($config, array(), $dispatcher)->onKernelRequest($event);
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
            ->with('kernel.response', m::type('callable'))
            ->andReturnUsing(function ($cb) use (&$callback) {
                $callback = $cb;
            });

        $event = new GetResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($config, array(), $dispatcher)->onKernelRequest($event);
        $event = new FilterResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST, new Response());
        $this->getListener($config, array(), $dispatcher)->onKernelResponse($event);
        $resp = $event->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $resp);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertEquals('http://example.com', $resp->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals(null, $resp->headers->get('Access-Control-Allow-Methods'));
        $this->assertEquals(null, $resp->headers->get('Access-Control-Allow-Headers'));
    }

    public function testPreflightedRequestNotMatchingSubdomain()
    {
        $config = array('/foo' => array(
            'allow_origin' => array(true),
            'allow_headers' => array('foo', 'bar'),
            'allow_methods' => array('POST', 'PUT'),
            'hosts' => array('^test\.'),
        ));

        // preflight
        $req = Request::create('/foo', 'OPTIONS', array(), array(), array(), array('HTTP_HOST' => 'example.com'));
        $req->headers->set('Origin', 'http://example.com');
        $req->headers->set('Access-Control-Request-Method', 'POST');
        $req->headers->set('Access-Control-Request-Headers', 'Foo, BAR');

        $dispatcher = m::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $event = new GetResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($config, array(), $dispatcher)->onKernelRequest($event);
        $resp = $event->getResponse();
        $this->assertEquals(NULL, $resp);
    }

    public function testPreflightedRequestMatchingSubdomain()
    {
        $config = array('/foo' => array(
            'allow_origin' => array(true),
            'allow_headers' => array('foo', 'bar'),
            'allow_methods' => array('POST', 'PUT'),
            'hosts' => array('^test\.'),
        ));

        // preflight
        $req = Request::create('/foo', 'OPTIONS', array(), array(), array(), array('HTTP_HOST' => 'test.example.com'));
        $req->headers->set('Origin', 'http://test.example.com');
        $req->headers->set('Access-Control-Request-Method', 'POST');
        $req->headers->set('Access-Control-Request-Headers', 'Foo, BAR');

        $dispatcher = m::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $event = new GetResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($config, array(), $dispatcher)->onKernelRequest($event);
        $resp = $event->getResponse();

        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertEquals('http://test.example.com', $resp->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('POST, PUT', $resp->headers->get('Access-Control-Allow-Methods'));
        $this->assertEquals('foo, bar', $resp->headers->get('Access-Control-Allow-Headers'));
    }

    public function testPreflightedRequestMatchingSubdomainDouble()
    {
        $config = array('/foo' => array(
            'allow_origin' => array(true),
            'allow_headers' => array('foo', 'bar'),
            'allow_methods' => array('POST', 'PUT'),
            'hosts' => array('first\.','^test\.stage\.','second\.stage\.'),
        ));

        // preflight
        $req = Request::create('/foo', 'OPTIONS', array(), array(), array(), array('HTTP_HOST' => 'test.stage.example.com'));
        $req->headers->set('Origin', 'http://test.stage.example.com');
        $req->headers->set('Access-Control-Request-Method', 'POST');
        $req->headers->set('Access-Control-Request-Headers', 'Foo, BAR');

        $dispatcher = m::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $event = new GetResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($config, array(), $dispatcher)->onKernelRequest($event);
        $resp = $event->getResponse();

        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertEquals('http://test.stage.example.com', $resp->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('POST, PUT', $resp->headers->get('Access-Control-Allow-Methods'));
        $this->assertEquals('foo, bar', $resp->headers->get('Access-Control-Allow-Headers'));
    }
}
