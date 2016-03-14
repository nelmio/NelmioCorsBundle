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

    public function getListener(array $options = array())
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
            ),
            $options
        );

        $resolver = m::mock('Nelmio\CorsBundle\Options\ResolverInterface');
        $resolver->shouldReceive('getOptions')->andReturn($mergedOptions);

        return new CorsListener($resolver);
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

        $event = new GetResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($options)->onKernelRequest($event);
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

        $event = new GetResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($options)->onKernelRequest($event);
        $event = new FilterResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST, new Response());
        $this->getListener($options)->onKernelResponse($event);
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

        $event = new GetResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST);
        $this->getListener($options)->onKernelRequest($event);
        $resp = $event->getResponse();
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $resp);
        $this->assertEquals(200, $resp->getStatusCode());
        $this->assertEquals('http://example.com', $resp->headers->get('Access-Control-Allow-Origin'));
        $this->assertEquals('LINK, PUT, Link', $resp->headers->get('Access-Control-Allow-Methods'));
    }

    public function testRequestWithOriginButNo()
    {
        $options = array(
            'allow_origin' => array(),
        );

        $req = Request::create('/foo', 'GET');
        $req->headers->set('Host', 'example.com');
        $req->headers->set('Origin', 'http://evil.com');

        $event = new FilterResponseEvent(m::mock('Symfony\Component\HttpKernel\HttpKernelInterface'), $req, HttpKernelInterface::MASTER_REQUEST, new Response());
        $this->getListener($options)->onKernelResponse($event);

        $this->assertFalse($event->getResponse()->headers->has('Access-Control-Allow-Origin'));
    }
}
