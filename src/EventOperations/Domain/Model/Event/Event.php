<?php

declare(strict_types=1);

namespace App\EventOperations\Domain\Model\Event;

use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Property\PropertyId;
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
        private ?PropertyId $propertyId = null,
        private ?CompanyId $companyId = null,
        private ?EventSize $size = null,
        private ?int $estimatedAttendees = null,
        private ?EventSector $sector = null,
        private ?EventTypology $typology = null,
        private DateRange $assembly = new DateRange(),
        private DateRange $celebration = new DateRange(),
        private DateRange $breakdown = new DateRange(),
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

    public function propertyId(): ?PropertyId
    {
        return $this->propertyId;
    }

    public function companyId(): ?CompanyId
    {
        return $this->companyId;
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

    public function assembly(): DateRange
    {
        return $this->assembly;
    }

    public function celebration(): DateRange
    {
        return $this->celebration;
    }

    public function breakdown(): DateRange
    {
        return $this->breakdown;
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
        ?PropertyId $propertyId,
        ?CompanyId $companyId,
        ?EventSize $size,
        ?int $estimatedAttendees,
        ?EventSector $sector,
        ?EventTypology $typology,
        DateRange $assembly,
        DateRange $celebration,
        DateRange $breakdown,
        ?AccountId $accountId,
    ): void {
        $this->recordApplyAndPublish(
            new EventRegistered(
                eventId: $this->id(),
                name: $name,
                location: $location,
                propertyId: $propertyId,
                companyId: $companyId,
                size: $size,
                estimatedAttendees: $estimatedAttendees,
                sector: $sector,
                typology: $typology,
                assembly: $assembly,
                celebration: $celebration,
                breakdown: $breakdown,
                accountId: $accountId,
            ),
        );
    }

    public function update(
        string $name,
        ?DestinationId $location,
        ?PropertyId $propertyId,
        ?CompanyId $companyId,
        ?EventSize $size,
        ?int $estimatedAttendees,
        ?EventSector $sector,
        ?EventTypology $typology,
        DateRange $assembly,
        DateRange $celebration,
        DateRange $breakdown,
    ): void {
        $this->recordApplyAndPublish(
            new EventUpdated(
                eventId: $this->id(),
                name: $name,
                location: $location,
                propertyId: $propertyId,
                companyId: $companyId,
                size: $size,
                estimatedAttendees: $estimatedAttendees,
                sector: $sector,
                typology: $typology,
                assembly: $assembly,
                celebration: $celebration,
                breakdown: $breakdown,
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
        $this->propertyId = $event->propertyId();
        $this->companyId = $event->companyId();
        $this->size = $event->size();
        $this->estimatedAttendees = $event->estimatedAttendees();
        $this->sector = $event->sector();
        $this->typology = $event->typology();
        $this->assembly = $event->assembly();
        $this->celebration = $event->celebration();
        $this->breakdown = $event->breakdown();
        $this->accountId = $event->accountId();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyEventUpdated(EventUpdated $event): void
    {
        $this->name = $event->name();
        $this->location = $event->location();
        $this->propertyId = $event->propertyId();
        $this->companyId = $event->companyId();
        $this->size = $event->size();
        $this->estimatedAttendees = $event->estimatedAttendees();
        $this->sector = $event->sector();
        $this->typology = $event->typology();
        $this->assembly = $event->assembly();
        $this->celebration = $event->celebration();
        $this->breakdown = $event->breakdown();
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
