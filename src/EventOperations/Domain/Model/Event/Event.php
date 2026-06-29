<?php

declare(strict_types=1);

namespace App\EventOperations\Domain\Model\Event;

use App\Property\Domain\Model\Destination\DestinationId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class Event implements \Stringable
{
    use AggregateRoot;

    /** @param array<DomainEvent> $events */
    public function __construct(
        private EventId $id,
        private string $name = '',
        private ?DestinationId $location = null,
        private ?EventSize $size = null,
        private ?int $estimatedAttendees = null,
        private ?EventSector $sector = null,
        private ?EventTypology $typology = null,
        private DateRange $setup = new DateRange(),
        private DateRange $celebration = new DateRange(),
        private DateRange $dismantling = new DateRange(),
        private ?AccountId $accountId = null,
        private ?bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): EventId
    {
        return $this->id;
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

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function isRemoved(): bool
    {
        return $this->removed ?? false;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function register(
        string $name,
        ?DestinationId $location,
        ?EventSize $size,
        ?int $estimatedAttendees,
        ?EventSector $sector,
        ?EventTypology $typology,
        DateRange $setup,
        DateRange $celebration,
        DateRange $dismantling,
        ?AccountId $accountId,
    ): void {
        $this->recordApplyAndPublish(
            new EventRegistered(
                eventId: $this->id(),
                name: $name,
                location: $location,
                size: $size,
                estimatedAttendees: $estimatedAttendees,
                sector: $sector,
                typology: $typology,
                setup: $setup,
                celebration: $celebration,
                dismantling: $dismantling,
                accountId: $accountId,
            ),
        );
    }

    public function update(
        string $name,
        ?DestinationId $location,
        ?EventSize $size,
        ?int $estimatedAttendees,
        ?EventSector $sector,
        ?EventTypology $typology,
        DateRange $setup,
        DateRange $celebration,
        DateRange $dismantling,
    ): void {
        $this->recordApplyAndPublish(
            new EventUpdated(
                eventId: $this->id(),
                name: $name,
                location: $location,
                size: $size,
                estimatedAttendees: $estimatedAttendees,
                sector: $sector,
                typology: $typology,
                setup: $setup,
                celebration: $celebration,
                dismantling: $dismantling,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new EventRemoved(
                eventId: $this->id(),
            ),
        );
    }

    public function applyEventRegistered(EventRegistered $event): void
    {
        $this->name = $event->name();
        $this->location = $event->location();
        $this->size = $event->size();
        $this->estimatedAttendees = $event->estimatedAttendees();
        $this->sector = $event->sector();
        $this->typology = $event->typology();
        $this->setup = $event->setup();
        $this->celebration = $event->celebration();
        $this->dismantling = $event->dismantling();
        $this->accountId = $event->accountId();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyEventUpdated(EventUpdated $event): void
    {
        $this->name = $event->name();
        $this->location = $event->location();
        $this->size = $event->size();
        $this->estimatedAttendees = $event->estimatedAttendees();
        $this->sector = $event->sector();
        $this->typology = $event->typology();
        $this->setup = $event->setup();
        $this->celebration = $event->celebration();
        $this->dismantling = $event->dismantling();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyEventRemoved(EventRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
