<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Integration;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;

class FlushIntegrationOutboxSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly BufferedIntegrationEventPublisher $publisher) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'markFailed',
            KernelEvents::TERMINATE => 'flushAndDiscard',
            ConsoleEvents::ERROR => 'markFailed',
            ConsoleEvents::TERMINATE => 'onConsoleTerminate',
            WorkerMessageHandledEvent::class => 'flush',
            WorkerMessageFailedEvent::class => 'discard',
        ];
    }

    public function markFailed(): void
    {
        $this->publisher->markFailed();
    }

    public function flushAndDiscard(): void
    {
        $this->publisher->flush();
        $this->publisher->discard();
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        if (0 !== $event->getExitCode()) {
            $this->publisher->discard();

            return;
        }

        $this->publisher->flush();
    }

    public function flush(): void
    {
        $this->publisher->flush();
    }

    public function discard(): void
    {
        $this->publisher->discard();
    }
}
