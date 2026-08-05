<?php

declare(strict_types=1);

namespace App\EventOperations\Domain\Model\Event;

use App\EventOperations\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EventVenueLocationSet extends DomainEvent
{
    protected static string $eventName = 'eventVenueLocationSet';

    public function __construct(
        private EventId $eventId,
        private ?string $venuePlaceReference,
        private ?string $venueLabel,
        private ?float $venueLatitude,
        private ?float $venueLongitude,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function eventId(): EventId
    {
        return $this->eventId;
    }

    public function venuePlaceReference(): ?string
    {
        return $this->venuePlaceReference;
    }

    public function venueLabel(): ?string
    {
        return $this->venueLabel;
    }

    public function venueLatitude(): ?float
    {
        return $this->venueLatitude;
    }

    public function venueLongitude(): ?float
    {
        return $this->venueLongitude;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->eventId();
    }

    public function payload(): array
    {
        return [
            'eventId' => (string) $this->eventId,
            'venuePlaceReference' => $this->venuePlaceReference,
            'venueLabel' => $this->venueLabel,
            'venueLatitude' => $this->venueLatitude,
            'venueLongitude' => $this->venueLongitude,
        ];
    }

    public static function rebuildFromPayload(DomainEventId $id, \DateTimeImmutable $occurredOn, array $payload = []): static
    {
        return new self(
            eventId: new EventId($payload['eventId']),
            venuePlaceReference: $payload['venuePlaceReference'] ?? null,
            venueLabel: $payload['venueLabel'] ?? null,
            venueLatitude: $payload['venueLatitude'] ?? null,
            venueLongitude: $payload['venueLongitude'] ?? null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
