<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\EventSubscriber;

use App\Shared\Infrastructure\Live\BufferedLiveUpdates;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;

class FlushLiveUpdatesSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly BufferedLiveUpdates $liveUpdates) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'flush',
            ConsoleEvents::TERMINATE => 'flush',
            WorkerMessageHandledEvent::class => 'flush',
            WorkerMessageFailedEvent::class => 'flush',
        ];
    }

    public function flush(): void
    {
        $this->liveUpdates->flush();
    }
}
