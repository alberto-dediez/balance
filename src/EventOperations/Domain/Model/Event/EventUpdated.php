<?php

declare(strict_types=1);

namespace App\EventOperations\Domain\Model\Event;

use App\EventOperations\Domain\DomainEvent;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EventUpdated extends DomainEvent
{
    protected static string $eventName = 'eventUpdated';

    public function __construct(
        private EventId $eventId,
        private string $name,
        private ?DestinationId $location,
        private ?EventSize $size,
        private ?int $estimatedAttendees,
        private ?EventSector $sector,
        private ?EventTypology $typology,
        private DateRange $setup,
        private DateRange $celebration,
        private DateRange $dismantling,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function eventId(): EventId
    {
        return $this->eventId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function location(): ?DestinationId
    {
        return $this->location;
    }

    public function size(): ?EventSize
    {
        return $this->size;
    }

    public function estimatedAttendees(): ?int
    {
        return $this->estimatedAttendees;
    }

    public function sector(): ?EventSector
    {
        return $this->sector;
    }

    public function typology(): ?EventTypology
    {
        return $this->typology;
    }

    public function setup(): DateRange
    {
        return $this->setup;
    }

    public function celebration(): DateRange
    {
        return $this->celebration;
    }

    public function dismantling(): DateRange
    {
        return $this->dismantling;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->eventId();
    }

    public function payload(): array
    {
        return [
            'eventId' => (string) $this->eventId,
            'name' => $this->name,
            'location' => $this->location ? (string) $this->location : null,
            'size' => $this->size?->value,
            'estimatedAttendees' => $this->estimatedAttendees,
            'sector' => $this->sector?->value,
            'typology' => $this->typology?->value,
            'setupStartDate' => $this->setup->startDate()?->format(\DateTimeInterface::ATOM),
            'setupEndDate' => $this->setup->endDate()?->format(\DateTimeInterface::ATOM),
            'celebrationStartDate' => $this->celebration->startDate()?->format(\DateTimeInterface::ATOM),
            'celebrationEndDate' => $this->celebration->endDate()?->format(\DateTimeInterface::ATOM),
            'dismantlingStartDate' => $this->dismantling->startDate()?->format(\DateTimeInterface::ATOM),
            'dismantlingEndDate' => $this->dismantling->endDate()?->format(\DateTimeInterface::ATOM),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new EventUpdated(
            eventId: new EventId($payload['eventId']),
            name: $payload['name'],
            location: isset($payload['location']) ? new DestinationId($payload['location']) : null,
            size: isset($payload['size']) ? EventSize::tryFrom($payload['size']) : null,
            estimatedAttendees: $payload['estimatedAttendees'] ?? null,
            sector: isset($payload['sector']) ? EventSector::tryFrom($payload['sector']) : null,
            typology: isset($payload['typology']) ? EventTypology::tryFrom($payload['typology']) : null,
            setup: new DateRange(
                startDate: isset($payload['setupStartDate']) ? new \DateTimeImmutable($payload['setupStartDate']) : null,
                endDate: isset($payload['setupEndDate']) ? new \DateTimeImmutable($payload['setupEndDate']) : null,
            ),
            celebration: new DateRange(
                startDate: isset($payload['celebrationStartDate']) ? new \DateTimeImmutable($payload['celebrationStartDate']) : null,
                endDate: isset($payload['celebrationEndDate']) ? new \DateTimeImmutable($payload['celebrationEndDate']) : null,
            ),
            dismantling: new DateRange(
                startDate: isset($payload['dismantlingStartDate']) ? new \DateTimeImmutable($payload['dismantlingStartDate']) : null,
                endDate: isset($payload['dismantlingEndDate']) ? new \DateTimeImmutable($payload['dismantlingEndDate']) : null,
            ),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
