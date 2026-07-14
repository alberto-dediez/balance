<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EventCarbonFootprintCompleted extends DomainEvent
{
    protected static string $eventName = 'eventCarbonFootprintCompleted';

    public function __construct(
        private EventCarbonFootprintId $eventCarbonFootprintId,
        private Emissions $totalEmissions,
        private Emissions $emissionsPerAttendee,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function eventCarbonFootprintId(): EventCarbonFootprintId
    {
        return $this->eventCarbonFootprintId;
    }

    public function totalEmissions(): Emissions
    {
        return $this->totalEmissions;
    }

    public function emissionsPerAttendee(): Emissions
    {
        return $this->emissionsPerAttendee;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->eventCarbonFootprintId();
    }

    public function payload(): array
    {
        return [
            'eventCarbonFootprintId' => (string) $this->eventCarbonFootprintId,
            'totalEmissionsCo2eq' => $this->totalEmissions->co2eq(),
            'totalEmissionsCo2' => $this->totalEmissions->co2(),
            'totalEmissionsCh4' => $this->totalEmissions->ch4(),
            'totalEmissionsN2o' => $this->totalEmissions->n2o(),
            'emissionsPerAttendeeCo2eq' => $this->emissionsPerAttendee->co2eq(),
            'emissionsPerAttendeeCo2' => $this->emissionsPerAttendee->co2(),
            'emissionsPerAttendeeCh4' => $this->emissionsPerAttendee->ch4(),
            'emissionsPerAttendeeN2o' => $this->emissionsPerAttendee->n2o(),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            eventCarbonFootprintId: new EventCarbonFootprintId($payload['eventCarbonFootprintId']),
            totalEmissions: new Emissions(
                co2eq: $payload['totalEmissionsCo2eq'] ?? '0',
                co2: $payload['totalEmissionsCo2'] ?? '0',
                ch4: $payload['totalEmissionsCh4'] ?? '0',
                n2o: $payload['totalEmissionsN2o'] ?? '0',
            ),
            emissionsPerAttendee: new Emissions(
                co2eq: $payload['emissionsPerAttendeeCo2eq'] ?? '0',
                co2: $payload['emissionsPerAttendeeCo2'] ?? '0',
                ch4: $payload['emissionsPerAttendeeCh4'] ?? '0',
                n2o: $payload['emissionsPerAttendeeN2o'] ?? '0',
            ),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
