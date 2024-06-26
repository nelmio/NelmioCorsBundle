<?php

namespace Nelmio\CorsBundle\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * When a response is cacheable the `Vary` header has to include `Origin`.
 */
final class CacheableResponseVaryListener
{
    public function onResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        if (!$response->isCacheable()) {
            return;
        }

        if (!\in_array('Origin', $response->getVary(), true)) {
            $response->setVary(array_merge(['Origin'], $response->getVary()));
        }
    }
}
