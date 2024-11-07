<?php

namespace App\Tests\Unit\Shared\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Event\DomainEventSubscriber;

class VeryPickyDomainEventSubscriber implements DomainEventSubscriber
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
        return false;
    }

    public function events()
    {
        return $this->events;
    }
}