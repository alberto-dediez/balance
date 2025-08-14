<?php

namespace App\Shared\Infrastructure\Symfony\Event;

use App\Shared\Domain\Event\DomainEventPublisher;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class KernelResponseListener
{
    public function __invoke(ResponseEvent $event): void
    {
        $domainEventPublisher = DomainEventPublisher::instance()
            ->unlock();    
    }
}