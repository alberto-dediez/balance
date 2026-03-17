<?php

namespace App\Shared\Infrastructure\Symfony\Event;

use App\Shared\Domain\Event\DomainEventPublisher;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

#[AsEventListener]
class KernelRequestListener
{
    public function __construct(
        #[AutowireIterator('domain.event.subscriber')]
        iterable $subscribers,
         #[AutowireIterator('application.command.handler')]
        iterable $handlers
    ) {
        foreach ($subscribers as $subscriber) {
            $subscriber->register();
        }

        foreach ($handlers as $handler) {
            $handler->register();
        }
    }

    public function __invoke(RequestEvent $event): void
    {
        DomainEventPublisher::instance()->lock();
    }
}
