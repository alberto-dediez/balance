<?php

namespace App\Shared\Domain\Model;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventPublisher;

trait AggregateRoot
{
    protected function recordApplyAndPublish(DomainEvent $event)
    {
        $this->record($event);
        $this->apply($event);
        $this->publish($event);
    }

    protected function record(DomainEvent $event)
    {
        $this->events[(string) $event->id()] = $event;
    }

    protected function apply(DomainEvent $event)
    {
        $method = 'apply' .  ucfirst($event->eventName());

        $this->$method($event);
    }

    protected function publish(DomainEvent $event)
    {
        DomainEventPublisher::instance()->publish($event);
    }

    public function recordedEvents()
    {
        return $this->events;
    }

    public function rebuild()
    {
        if (null !== $this->events) {
            return ;
        }

        if (count($this->events) == 0) {
            return ;
        }

        foreach ($this->events as $event) {
            $this->apply($event);
        }
    }

    public function clearEvents()
    {
        $this->events = [];
    }
}