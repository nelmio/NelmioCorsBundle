<?php

/*
 * This file is part of the NelmioCorsBundle.
 *
 * (c) Nelmio <hello@nelm.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\CorsBundle\EventListener;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Adds CORS headers and handles pre-flight requests
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class CorsListener
{
    /**
     * Simple headers as defined in the spec should always be accepted
     */
    protected static $simpleHeaders = array(
        'accept',
        'accept-language',
        'content-language',
        'origin',
    );

    protected $dispatcher;
    protected $paths;
    protected $defaults;
    protected $options;

    public function __construct(EventDispatcherInterface $dispatcher, array $paths, array $defaults = array())
    {
        $this->dispatcher = $dispatcher;
        $this->paths = $paths;
        $this->defaults = $defaults;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $request = $event->getRequest();

        // skip if not a CORS request
        if (!$request->headers->has('Origin')) {
            return;
        }

        $currentPath = $request->getPathInfo() ?: '/';

        foreach ($this->paths as $path => $options) {
            if (preg_match('{'.$path.'}i', $currentPath)) {
                $options = array_merge($this->defaults, $options);
                $options['allow_headers'] = array_map('strtolower', $options['allow_headers']);

                // perform preflight checks
                if ('OPTIONS' === $request->getMethod()) {
                    $event->setResponse($this->getPreflightResponse($request, $options));
                    return;
                }

                if (!$this->checkOrigin($request, $options)) {
                    $response = new Response('', 403, array('Access-Control-Allow-Origin' => 'null'));
                    $event->setResponse($response);
                    return;
                }

                $this->dispatcher->addListener('kernel.response', array($this, 'onKernelResponse'));
                $this->options = $options;
                return;
            }
        }
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();
        // add CORS response headers
        $response->headers->set('Access-Control-Allow-Origin', $this->options['allow_origin'] === true ? '*' : $request->headers->get('Origin'));
        if ($this->options['allow_credentials']) {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }
        if ($this->options['expose_headers']) {
            $response->headers->set('Access-Control-Expose-Headers', strtolower(implode(', ', $this->options['expose_headers'])));
        }
    }

    protected function getPreflightResponse($request, $options)
    {
        $response = new Response();

        if ($options['allow_credentials']) {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }
        if ($options['allow_methods']) {
            $response->headers->set('Access-Control-Allow-Methods', strtoupper(implode(', ', $options['allow_methods'])));
        }
        if ($options['allow_headers']) {
            $response->headers->set('Access-Control-Allow-Headers', implode(', ', $options['allow_headers']));
        }
        if ($options['max_age']) {
            $response->headers->set('Access-Control-Max-Age', $options['max_age']);
        }

        if (!$this->checkOrigin($request, $options)) {
            $response->headers->set('Access-Control-Allow-Origin', 'null');
            return $response;
        }

        $response->headers->set('Access-Control-Allow-Origin', $options['allow_origin'] === true ? '*' : $request->headers->get('Origin'));

        // check request method
        if (!in_array($request->headers->get('Access-Control-Request-Method'), $options['allow_methods'], true)) {
            $response->setStatusCode(405);
            return $response;
        }

        // check request headers
        $headers = trim(strtolower($request->headers->get('Access-Control-Request-Headers')));
        if ($headers) {
            foreach (preg_split('{, *}', $headers) as $header) {
                if (in_array($header, self::$simpleHeaders, true)) {
                    continue;
                }
                if (!in_array($header, $options['allow_headers'], true)) {
                    $response->setStatusCode(400);
                    $response->setContent('Unauthorized header '.$header);
                    break;
                }
            }
        }

        return $response;
    }

    protected function checkOrigin($request, $options)
    {
        // check origin
        $origin = $request->headers->get('Origin');
        if ($options['allow_origin'] === true || in_array($origin, $options['allow_origin'])) {
            return true;
        }

        return false;
    }
}
