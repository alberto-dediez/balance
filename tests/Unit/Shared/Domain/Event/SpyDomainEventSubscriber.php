<?php

namespace App\Tests\Unit\Shared\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\RegisterTrait;
use App\Shared\Domain\Event\DomainEventSubscriber;

class SpyDomainEventSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    /** @param DomainEvent[] $events  */
    public function __construct(private array $events = [])
    {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        $this->events[] = $event;
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return true;
    }

    /** @return DomainEvent[] $events  */
    public function events(): array
    {
        return $this->events;
    }

    public function lastEvent(): ?DomainEvent
    {
        return $this->events[count($this->events)-1] ?? null
        ;
    }
}
