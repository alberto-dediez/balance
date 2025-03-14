<?php

namespace App\Security\Domain\Model\RecordedEvent;

use DateTimeImmutable;
use App\Shared\Domain\Event\DomainEvent;
use App\Security\Domain\Model\User\UserId;

class RecordedEvent
{
    private RecordedEventId $id;

    private UserId|string|null $userId;
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
        if (is_string($this->userId)) {
            $this->userId = new UserId($this->userId);
        }

        return $this->userId;
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

    public function payload(): ?array
    {
        return $this->payload;
    }
}