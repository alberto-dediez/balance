<?php

namespace App\Shared\Infrastructure\Symfony\Event;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

#[AsEventListener]
class KernelRequestListener
{
    public function __construct(
        #[AutowireIterator('domain.event.subscriber')]
        private iterable $subscribers
    ) {
        foreach ($subscribers as $subscriber) {
            $subscriber->register();
        }
    }

    public function __invoke(RequestEvent $event): void
    {
        
    }
}