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

use Nelmio\CorsBundle\Options\ResolverInterface;
use Nelmio\CorsBundle\Options\ProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Adds CORS headers and handles pre-flight requests
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @phpstan-import-type CorsCompleteOptions from ProviderInterface
 */
class CorsListener
{
    public const SHOULD_ALLOW_ORIGIN_ATTR = '_nelmio_cors_should_allow_origin';
    public const SHOULD_FORCE_ORIGIN_ATTR = '_nelmio_cors_should_force_origin';

    /**
     * Simple headers as defined in the spec should always be accepted
     * @var list<string>
     * @deprecated
     */
    protected static $simpleHeaders = self::SIMPLE_HEADERS;

    protected const SIMPLE_HEADERS = [
        'accept',
        'accept-language',
        'content-language',
        'origin',
    ];

    /** @var ResolverInterface */
    protected $configurationResolver;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(ResolverInterface $configurationResolver, ?LoggerInterface $logger = null)
    {
        $this->configurationResolver = $configurationResolver;

        if (null === $logger) {
            $logger = new NullLogger();
        }
        $this->logger = $logger;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (HttpKernelInterface::MAIN_REQUEST !== $event->getRequestType()) {
            $this->logger->debug('Not a master type request, skipping CORS checks.');

            return;
        }

        $request = $event->getRequest();

        // @phpstan-ignore booleanNot.alwaysFalse (an invalid overridden configuration resolver may not be trustworthy)
        if (!$options = $this->configurationResolver->getOptions($request)) {
            $this->logger->debug('Could not get options for request, skipping CORS checks.');
            return;
        }

        // if the "forced_allow_origin_value" option is set, add a listener which will set or override the "Access-Control-Allow-Origin" header
        if (!empty($options['forced_allow_origin_value'])) {
            $this->logger->debug(sprintf(
                "The 'forced_allow_origin_value' option is set to '%s', adding a listener to set or override the 'Access-Control-Allow-Origin' header.",
                $options['forced_allow_origin_value']
            ));

            $request->attributes->set(self::SHOULD_FORCE_ORIGIN_ATTR, true);
        }

        // skip if not a CORS request
        if (!$request->headers->has('Origin')) {
            $this->logger->debug("Request does not have 'Origin' header, skipping CORS.");

            return;
        }

        if ($options['skip_same_as_origin'] && $request->headers->get('Origin') === $request->getSchemeAndHttpHost()) {
            $this->logger->debug("The 'Origin' header of the request equals the scheme and host the request was sent to, skipping CORS.");

            return;
        }

        // perform preflight checks
        if ('OPTIONS' === $request->getMethod() &&
            ($request->headers->has('Access-Control-Request-Method') ||
                $request->headers->has('Access-Control-Request-Private-Network'))
        ) {
            $this->logger->debug("Request is a preflight check, setting event response now.");

            $event->setResponse($this->getPreflightResponse($request, $options));

            return;
        }

        if (!$this->checkOrigin($request, $options)) {
            $this->logger->debug("Origin check failed.");

            return;
        }

        $this->logger->debug("Origin is allowed, proceed with adding CORS response headers.");

        $request->attributes->set(self::SHOULD_ALLOW_ORIGIN_ATTR, true);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (HttpKernelInterface::MAIN_REQUEST !== $event->getRequestType()) {
            $this->logger->debug("Not a master type request, skip adding CORS response headers.");

            return;
        }

        $request = $event->getRequest();

        $shouldAllowOrigin = $request->attributes->getBoolean(self::SHOULD_ALLOW_ORIGIN_ATTR);
        $shouldForceOrigin = $request->attributes->getBoolean(self::SHOULD_FORCE_ORIGIN_ATTR);

        if (!$shouldAllowOrigin && !$shouldForceOrigin) {
            $this->logger->debug("The origin should not be allowed and not be forced, skip adding CORS response headers.");

            return;
        }

        // @phpstan-ignore booleanNot.alwaysFalse (an invalid overridden configuration resolver may not be trustworthy)
        if (!$options = $this->configurationResolver->getOptions($request)) {
            $this->logger->debug("Could not resolve options for request, skip adding CORS response headers.");

            return;
        }

        if ($shouldAllowOrigin) {
            $response = $event->getResponse();
            // add CORS response headers
            $origin = $request->headers->get('Origin');

            $this->logger->debug(sprintf("Setting 'Access-Control-Allow-Origin' response header to '%s'.", $origin));

            $response->headers->set('Access-Control-Allow-Origin', $origin);

            if ($options['allow_credentials']) {
                $this->logger->debug("Setting 'Access-Control-Allow-Credentials' to 'true'.");

                $response->headers->set('Access-Control-Allow-Credentials', 'true');
            }
            if ($options['expose_headers']) {
                $headers = strtolower(implode(', ', $options['expose_headers']));

                $this->logger->debug(sprintf("Setting 'Access-Control-Expose-Headers' response header to '%s'.", $headers));

                $response->headers->set('Access-Control-Expose-Headers', $headers);
            }
        }

        if ($shouldForceOrigin) {
            assert(isset($options['forced_allow_origin_value']));
            $this->logger->debug(sprintf("Setting 'Access-Control-Allow-Origin' response header to '%s'.", $options['forced_allow_origin_value']));

            $event->getResponse()->headers->set('Access-Control-Allow-Origin', $options['forced_allow_origin_value']);
        }
    }

    /**
     * @phpstan-param CorsCompleteOptions $options
     */
    protected function getPreflightResponse(Request $request, array $options): Response
    {
        $response = new Response();
        $response->setVary(['Origin']);

        if ($options['allow_credentials']) {
            $this->logger->debug("Setting 'Access-Control-Allow-Credentials' response header to 'true'.");

            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }
        if ($options['allow_methods']) {
            $methods = implode(', ', $options['allow_methods']);

            $this->logger->debug(sprintf("Setting 'Access-Control-Allow-Methods' response header to '%s'.", $methods));

            $response->headers->set('Access-Control-Allow-Methods', $methods);
        }
        if ($options['allow_headers']) {
            $headers = $this->isWildcard($options, 'allow_headers')
                ? $request->headers->get('Access-Control-Request-Headers')
                : implode(', ', $options['allow_headers']); // @phpstan-ignore argument.type (isWildcard guarantees this is an array but PHPStan does not know)

            if ($headers) {
                $this->logger->debug(sprintf("Setting 'Access-Control-Allow-Headers' response header to '%s'.", $headers));

                $response->headers->set('Access-Control-Allow-Headers', $headers);
            }
        }
        if ($options['max_age']) {
            $this->logger->debug(sprintf("Setting 'Access-Control-Max-Age' response header to '%d'.", $options['max_age']));

            $response->headers->set('Access-Control-Max-Age', (string) $options['max_age']);
        }

        if (!$this->checkOrigin($request, $options)) {
            $this->logger->debug("Removing 'Access-Control-Allow-Origin' response header.");

            $response->headers->remove('Access-Control-Allow-Origin');

            return $response;
        }

        $origin = $request->headers->get('Origin');

        $this->logger->debug(sprintf("Setting 'Access-Control-Allow-Origin' response header to '%s'", $origin));

        $response->headers->set('Access-Control-Allow-Origin', $origin);

        // check private network access
        if ($request->headers->has('Access-Control-Request-Private-Network')
            && strtolower((string) $request->headers->get('Access-Control-Request-Private-Network')) === 'true'
        ) {
            if ($options['allow_private_network']) {
                $this->logger->debug("Setting 'Access-Control-Allow-Private-Network' response header to 'true'.");

                $response->headers->set('Access-Control-Allow-Private-Network', 'true');
            } else {
                $response->setStatusCode(400);
                $response->setContent('Private Network Access is not allowed.');
            }
        }

        // check request method
        $method = strtoupper((string) $request->headers->get('Access-Control-Request-Method'));
        if (!in_array($method, $options['allow_methods'], true)) {
            $this->logger->debug(sprintf("Method '%s' is not allowed.", $method));

            $response->setStatusCode(405);

            return $response;
        }

        /**
         * We have to allow the header in the case-set as we received it by the client.
         * Firefox f.e. sends the LINK method as "Link", and we have to allow it like this or the browser will deny the
         * request.
         */
        if (!in_array($request->headers->get('Access-Control-Request-Method'), $options['allow_methods'], true)) {
            $options['allow_methods'][] = (string) $request->headers->get('Access-Control-Request-Method');
            $response->headers->set('Access-Control-Allow-Methods', implode(', ', $options['allow_methods']));
        }

        // check request headers
        $headers = $request->headers->get('Access-Control-Request-Headers');
        if ($headers && !$this->isWildcard($options, 'allow_headers')) {
            $headers = strtolower(trim($headers));
            $splitHeaders = preg_split('{, *}', $headers);
            if (false === $splitHeaders) {
                throw new \RuntimeException('Failed splitting '.$headers);
            }
            foreach ($splitHeaders as $header) {
                if (in_array($header, self::SIMPLE_HEADERS, true)) {
                    continue;
                }
                if (!in_array($header, $options['allow_headers'], true)) { // @phpstan-ignore argument.type (isWildcard guarantees this is an array but PHPStan does not know)
                    $sanitizedMessage = htmlentities('Unauthorized header '.$header, ENT_QUOTES, 'UTF-8');
                    $response->setStatusCode(400);
                    $response->setContent($sanitizedMessage);
                    break;
                }
            }
        }

        return $response;
    }

    /**
     * @param CorsCompleteOptions $options
     */
    protected function checkOrigin(Request $request, array $options): bool
    {
        // check origin
        $origin = (string) $request->headers->get('Origin');

        if ($this->isWildcard($options, 'allow_origin')) {
            return true;
        }

        if ($options['origin_regex'] === true) {
            // origin regex matching
            foreach ($options['allow_origin'] as $originRegexp) { // @phpstan-ignore foreach.nonIterable (isWildcard guarantees this is an array but PHPStan does not know)
                $this->logger->debug(sprintf("Matching origin regex '%s' to origin '%s'.", $originRegexp, $origin));

                if (preg_match('{'.$originRegexp.'}i', $origin)) {
                    $this->logger->debug(sprintf("Origin regex '%s' matches origin '%s'.", $originRegexp, $origin));

                    return true;
                }
            }
        } else {
            // old origin matching
            if (in_array($origin, $options['allow_origin'], true)) { // @phpstan-ignore argument.type (isWildcard guarantees this is an array but PHPStan does not know)
                $this->logger->debug(sprintf("Origin '%s' is allowed.", $origin));

                return true;
            }
        }

        $this->logger->debug(sprintf("Origin '%s' is not allowed.", $origin));

        return false;
    }

    /**
     * @phpstan-param CorsCompleteOptions $options
     * @phpstan-param key-of<CorsCompleteOptions> $option
     */
    private function isWildcard(array $options, string $option): bool
    {
        $result = $options[$option] === true || (is_array($options[$option]) && in_array('*', $options[$option], true));

        $this->logger->debug(sprintf("Option '%s' is %s a wildcard.", $option, $result ? '' : 'not'));

        return $result;
    }
}
