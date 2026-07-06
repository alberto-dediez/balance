<?php

declare(strict_types=1);

namespace App\EventOperations\Domain\Model\Event;

use App\EventOperations\Domain\DomainEvent;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EventRegistered extends DomainEvent
{
    protected static string $eventName = 'eventRegistered';

    public function __construct(
        private EventId $eventId,
        private string $name,
        private ?DestinationId $location,
        private ?PropertyId $propertyId,
        private ?CompanyId $companyId,
        private ?EventSize $size,
        private ?int $estimatedAttendees,
        private ?EventSector $sector,
        private ?EventTypology $typology,
        private DateRange $assembly,
        private DateRange $celebration,
        private DateRange $breakdown,
        private ?AccountId $accountId,
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
            'propertyId' => $this->propertyId ? (string) $this->propertyId : null,
            'companyId' => $this->companyId ? (string) $this->companyId : null,
            'size' => $this->size?->value,
            'estimatedAttendees' => $this->estimatedAttendees,
            'sector' => $this->sector?->value,
            'typology' => $this->typology?->value,
            'assemblyStartDate' => $this->assembly->startDate()?->format(\DateTimeInterface::ATOM),
            'assemblyEndDate' => $this->assembly->endDate()?->format(\DateTimeInterface::ATOM),
            'celebrationStartDate' => $this->celebration->startDate()?->format(\DateTimeInterface::ATOM),
            'celebrationEndDate' => $this->celebration->endDate()?->format(\DateTimeInterface::ATOM),
            'breakdownStartDate' => $this->breakdown->startDate()?->format(\DateTimeInterface::ATOM),
            'breakdownEndDate' => $this->breakdown->endDate()?->format(\DateTimeInterface::ATOM),
            'accountId' => $this->accountId ? (string) $this->accountId : null,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new EventRegistered(
            eventId: new EventId($payload['eventId']),
            name: $payload['name'],
            location: isset($payload['location']) ? new DestinationId($payload['location']) : null,
            propertyId: isset($payload['propertyId']) ? new PropertyId($payload['propertyId']) : null,
            companyId: isset($payload['companyId']) ? new CompanyId($payload['companyId']) : null,
            size: isset($payload['size']) ? EventSize::tryFrom($payload['size']) : null,
            estimatedAttendees: $payload['estimatedAttendees'] ?? null,
            sector: isset($payload['sector']) ? EventSector::tryFrom($payload['sector']) : null,
            typology: isset($payload['typology']) ? EventTypology::tryFrom($payload['typology']) : null,
            assembly: new DateRange(
                startDate: isset($payload['assemblyStartDate']) ? new \DateTimeImmutable($payload['assemblyStartDate']) : null,
                endDate: isset($payload['assemblyEndDate']) ? new \DateTimeImmutable($payload['assemblyEndDate']) : null,
            ),
            celebration: new DateRange(
                startDate: isset($payload['celebrationStartDate']) ? new \DateTimeImmutable($payload['celebrationStartDate']) : null,
                endDate: isset($payload['celebrationEndDate']) ? new \DateTimeImmutable($payload['celebrationEndDate']) : null,
            ),
            breakdown: new DateRange(
                startDate: isset($payload['breakdownStartDate']) ? new \DateTimeImmutable($payload['breakdownStartDate']) : null,
                endDate: isset($payload['breakdownEndDate']) ? new \DateTimeImmutable($payload['breakdownEndDate']) : null,
            ),
            accountId: isset($payload['accountId']) ? new AccountId($payload['accountId']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
