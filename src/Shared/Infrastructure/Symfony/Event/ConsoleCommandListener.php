<?php

namespace App\Shared\Infrastructure\Symfony\Event;

use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

#[AsEventListener]
class ConsoleCommandListener
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

    public function __invoke(ConsoleCommandEvent $event): void
    {

    }
}
