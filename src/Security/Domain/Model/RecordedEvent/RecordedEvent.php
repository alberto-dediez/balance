<?php

namespace App\Security\Domain\Model\RecordedEvent;

use DateTimeImmutable;
use App\Shared\Domain\Event\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Event\EventRegistry;
use App\Shared\Domain\Model\AggregateRootId;

class RecordedEvent
{
    private RecordedEventId $id;

    private UserId|null $userId;
    private string|null $aggregateRootId;
    private DateTimeImmutable $occurredOn;
    private string $eventName;
    private string $context;
    private array $payload;

    public function __construct(UserId|string|null $userId, DomainEvent $event)
    {
        $this->id = new RecordedEventId(
            (string) $event->id()
        );

        $this->userId = $userId;

        $this->aggregateRootId = null !== $event->aggregateRootId() ? (string) $event->aggregateRootId() : null;
        $this->occurredOn = $event->occurredOn();
        $this->eventName = $event->eventName();
        $this->context = $event->context();
        $this->payload = $event->payload();
    }

    public function id(): RecordedEventId
    {
        return $this->id;
    }

    public function userId(): ?UserId
    {
        return $this->userId;
    }

    public function aggregateRootId(): ?string
    {
        return $this->aggregateRootId;
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function eventName(): string
    {
        return $this->eventName;
    }

    public function context(): string
    {
        return $this->context;
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function rebuild(): DomainEvent
    {
        $class = EventRegistry::instance()
            ->getEvent(
                context: $this->context(),
                eventName: $this->eventName()
            )
        ;

        if (null == $class) {
            throw new EventNotFoundException(
                $this->context(),
                $this->eventName()
            );
        }

        return $class::rebuildFromPayload(
            id: new DomainEventId($this->id()),
            occurredOn: $this->occurredOn(),
            payload: $this->payload()
        );
    }
}
