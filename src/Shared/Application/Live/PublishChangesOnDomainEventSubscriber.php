<?php

declare(strict_types=1);

namespace App\Shared\Application\Live;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class PublishChangesOnDomainEventSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private LiveUpdates $liveUpdates)
    {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        $this->liveUpdates->changed($event->context());
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return true;
    }
}
