<?php

namespace App\Tests\Unit\Shared\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\DomainEventPublisher;

class SpyDomainEventSubscriber implements DomainEventSubscriber
{
    public function __construct(private array $events = [])
    {
        $this->events = $events;

        DomainEventPublisher::instance()->subscribe(
            $this
        );
    }

    public function handle(DomainEvent $event)
    {
        $this->events[] = $event;
    }

    public function isSubscribedTo(DomainEvent $event)
    {
        return true;
    }

    public function events()
    {
        return $this->events;
    }

    public function lastEvent(): ?DomainEvent
    {
        return isset($this->events[count($this->events)-1])
            ? $this->events[count($this->events)-1]
            : null
        ;
    }
}
