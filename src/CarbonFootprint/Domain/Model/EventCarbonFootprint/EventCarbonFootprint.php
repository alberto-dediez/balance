<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\CarbonFootprint\Domain\Model\Emissions;
use App\EventOperations\Domain\Model\Event\DateRange;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;

class EventCarbonFootprint implements \Stringable
{
    use AggregateRoot;

    /** @param array<\App\Shared\Domain\Event\DomainEvent> $events */
    public function __construct(
        private EventCarbonFootprintId $id,
        private ?EventId $eventId = null,
        private ?AccountId $accountId = null,
        private DateRange $calculationPeriod = new DateRange(),
        private Emissions $totalEmissions = new Emissions(),
        private Emissions $emissionsPerAttendee = new Emissions(),
        private EventCarbonFootprintStatus $status = EventCarbonFootprintStatus::Started,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): EventCarbonFootprintId
    {
        return $this->id;
    }

    public function eventId(): ?EventId
    {
        return $this->eventId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function calculationPeriod(): DateRange
    {
        return $this->calculationPeriod;
    }

    public function totalEmissions(): Emissions
    {
        return $this->totalEmissions;
    }

    public function emissionsPerAttendee(): Emissions
    {
        return $this->emissionsPerAttendee;
    }

    public function status(): EventCarbonFootprintStatus
    {
        return $this->status;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
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
        EventId $eventId,
        AccountId $accountId,
        DateRange $calculationPeriod,
        Emissions $totalEmissions,
        Emissions $emissionsPerAttendee,
        EventCarbonFootprintStatus $status,
    ): void {
        $this->recordApplyAndPublish(
            new EventCarbonFootprintRegistered(
                eventCarbonFootprintId: $this->id(),
                eventId: $eventId,
                accountId: $accountId,
                calculationPeriod: $calculationPeriod,
                totalEmissions: $totalEmissions,
                emissionsPerAttendee: $emissionsPerAttendee,
                status: $status,
            ),
        );
    }

    public function update(
        DateRange $calculationPeriod,
        Emissions $totalEmissions,
        Emissions $emissionsPerAttendee,
        EventCarbonFootprintStatus $status,
    ): void {
        $this->recordApplyAndPublish(
            new EventCarbonFootprintUpdated(
                eventCarbonFootprintId: $this->id(),
                calculationPeriod: $calculationPeriod,
                totalEmissions: $totalEmissions,
                emissionsPerAttendee: $emissionsPerAttendee,
                status: $status,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new EventCarbonFootprintRemoved(
                eventCarbonFootprintId: $this->id(),
            ),
        );
    }

    public function applyEventCarbonFootprintRegistered(EventCarbonFootprintRegistered $event): void
    {
        $this->eventId = $event->eventId();
        $this->accountId = $event->accountId();
        $this->calculationPeriod = $event->calculationPeriod();
        $this->totalEmissions = $event->totalEmissions();
        $this->emissionsPerAttendee = $event->emissionsPerAttendee();
        $this->status = $event->status();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyEventCarbonFootprintUpdated(EventCarbonFootprintUpdated $event): void
    {
        $this->calculationPeriod = $event->calculationPeriod();
        $this->totalEmissions = $event->totalEmissions();
        $this->emissionsPerAttendee = $event->emissionsPerAttendee();
        $this->status = $event->status();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyEventCarbonFootprintRemoved(EventCarbonFootprintRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
