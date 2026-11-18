<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Symfony\Event;

use App\Shared\Infrastructure\Symfony\Event\ContentSecurityPolicyListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ContentSecurityPolicyListenerTest extends TestCase
{
    public function testItOnlyAllowsImagesFromTheSameOriginAndDataUrls(): void
    {
        $response = new Response();

        (new ContentSecurityPolicyListener())($this->event($response, HttpKernelInterface::MAIN_REQUEST));

        $this->assertSame("img-src 'self' data:", $response->headers->get('Content-Security-Policy'));
    }

    public function testItKeepsAPolicyAlreadySet(): void
    {
        $response = new Response();
        $response->headers->set('Content-Security-Policy', "default-src 'none'");

        (new ContentSecurityPolicyListener())($this->event($response, HttpKernelInterface::MAIN_REQUEST));

        $this->assertSame("default-src 'none'", $response->headers->get('Content-Security-Policy'));
    }

    public function testItIgnoresSubRequests(): void
    {
        $response = new Response();

        (new ContentSecurityPolicyListener())($this->event($response, HttpKernelInterface::SUB_REQUEST));

        $this->assertFalse($response->headers->has('Content-Security-Policy'));
    }

    private function event(Response $response, int $type): ResponseEvent
    {
        return new ResponseEvent($this->createMock(HttpKernelInterface::class), new Request(), $type, $response);
    }
}
