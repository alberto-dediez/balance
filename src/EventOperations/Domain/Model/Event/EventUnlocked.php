<?php

declare(strict_types=1);

namespace App\EventOperations\Domain\Model\Event;

use App\EventOperations\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EventUnlocked extends DomainEvent
{
    protected static string $eventName = 'eventUnlocked';

    public function __construct(
        private EventId $eventId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function eventId(): EventId
    {
        return $this->eventId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->eventId();
    }

    public function payload(): array
    {
        return [
            'eventId' => (string) $this->eventId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new EventUnlocked(
            eventId: new EventId($payload['eventId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
