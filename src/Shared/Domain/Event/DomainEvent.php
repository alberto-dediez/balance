<?php

namespace App\Shared\Domain\Event;

use DateTimeImmutable;
use App\Shared\Domain\Message\Message;
use App\Shared\Domain\Event\DomainEventId;

abstract class DomainEvent
{
    protected DateTimeImmutable $occurredOn;

    protected DomainEventId $id;

    protected string $eventName = 'generic';
    protected string $context = 'shared';

    public function __construct(?DomainEventId $id = null, ?DateTimeImmutable $occuredOn = null)
    {
        $this->id = null == $id ? DomainEventId::generate() : $id;

        $this->occurredOn = null == $occuredOn ? new DateTimeImmutable("NOW") : $occuredOn;
    }

    public function id(): DomainEventId
    {
        return $this->id;
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

    public function contextName(): string
    {
        return sprintf("%s.%s", $this->contextName(), $this->eventName());
    }

    abstract public function payload(): ?array;

    abstract public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, ?array $payload): static;
}