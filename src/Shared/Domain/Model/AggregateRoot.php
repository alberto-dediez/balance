<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model;

use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventPublisher;

trait AggregateRoot
{
    /**
     * @var DomainEvent[]
     */
    protected array $events = [];

    protected function recordApplyAndPublish(DomainEvent $event): void
    {
        $this->record($event);
        $this->apply($event);
        $this->publish($event);
    }

    protected function record(DomainEvent $event): void
    {
        $this->events[(string) $event->id()] = $event;
    }

    protected function apply(DomainEvent $event): void
    {
        $method = 'apply' . ucfirst($event->eventName());

        $this->$method($event);
    }

    protected function publish(DomainEvent $event): void
    {
        DomainEventPublisher::instance()->publish($event);
    }

    /**
     * @return DomainEvent[]
     */
    public function recordedEvents(): array
    {
        return $this->events;
    }

    public function rebuild(): void
    {
        if (empty($this->events)) {
            return;
        }

        foreach ($this->events as $event) {
            $this->apply($event);
        }
    }

    public function clearEvents(): void
    {
        $this->events = [];
    }
}
