<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Event;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

#[AsEventListener]
class ContentSecurityPolicyListener
{
    public const string HEADER = 'Content-Security-Policy';
    public const string POLICY = "img-src 'self' data:";

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $headers = $event->getResponse()->headers;

        if (!$headers->has(self::HEADER)) {
            $headers->set(self::HEADER, self::POLICY);
        }
    }
}
