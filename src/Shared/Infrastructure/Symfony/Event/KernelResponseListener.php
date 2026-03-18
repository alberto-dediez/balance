<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Event;

use App\Shared\Domain\Event\DomainEventPublisher;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

#[AsEventListener]
class KernelResponseListener
{
    public function __invoke(ResponseEvent $event): void
    {
        DomainEventPublisher::instance()
            ->unlock();
    }
}
