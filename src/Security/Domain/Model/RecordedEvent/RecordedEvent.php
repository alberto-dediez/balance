<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\RecordedEvent;

use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Event\EventRegistry;
use App\Shared\Domain\Model\AggregateRootId;

class RecordedEvent
{
    private readonly RecordedEventId $id;
    private readonly ?string $aggregateRootId;
    private readonly \DateTimeImmutable $occurredOn;
    private readonly string $eventName;
    private readonly string $context;
    /**
     * @var array<string, mixed>
     */
    private readonly array $payload;

    public function __construct(private readonly ?UserId $userId, DomainEvent $event, private readonly ?UserId $impersonatorId = null)
    {
        $this->id = new RecordedEventId(
            (string) $event->id(),
        );

        $this->aggregateRootId = $event->aggregateRootId() instanceof AggregateRootId ? (string) $event->aggregateRootId() : null;
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

    public function impersonatorId(): ?UserId
    {
        return $this->impersonatorId;
    }

    public function isImpersonated(): bool
    {
        return $this->impersonatorId instanceof UserId;
    }

    public function aggregateRootId(): ?string
    {
        return $this->aggregateRootId;
    }

    public function occurredOn(): \DateTimeImmutable
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

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    public function rebuild(): DomainEvent
    {
        $class = EventRegistry::instance()
            ->getEvent(
                context: $this->context(),
                eventName: $this->eventName(),
            )
        ;

        if (null === $class) {
            throw new EventNotFoundException($this->context(), $this->eventName());
        }

        /* @var class-string $class */
        return $class::rebuildFromPayload(
            id: new DomainEventId($this->id()->__toString()),
            occurredOn: $this->occurredOn(),
            payload: $this->payload(),
        );
    }
}
