<?php

namespace Nelmio\Tests\EventListener;

use Nelmio\CorsBundle\EventListener\CacheableResponseVaryListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class CacheableResponseVaryListenerTest extends TestCase
{
    /** @var CacheableResponseVaryListener */
    private $listener;
    /** @var ResponseEvent */
    private $event;
    /** @var Response */
    private $response;

    protected function setUp(): void
    {
        $this->event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $this->response = new Response()
        );
        $this->listener = new CacheableResponseVaryListener();
    }

    public function testOriginIsAddedAsVaryHeaderOnCacheableResponse(): void
    {
        $this->response->setTtl(300);
        $this->listener->onResponse($this->event);

        self::assertStringContainsString('Origin', (string) $this->event->getResponse()->headers->get('Vary'));
    }

    public function testOriginIsNotAddedAsVaryHeaderOnNonCacheableResponse(): void
    {
        $this->listener->onResponse($this->event);

        self::assertNull($this->event->getResponse()->headers->get('Vary'));
    }
}
