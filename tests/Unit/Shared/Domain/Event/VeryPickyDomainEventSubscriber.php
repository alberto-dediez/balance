<?php

namespace App\Tests\Unit\Shared\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\RegisterTrait;
use App\Shared\Domain\Event\DomainEventSubscriber;

class VeryPickyDomainEventSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private array $events = [])
    {
        $this->register();
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