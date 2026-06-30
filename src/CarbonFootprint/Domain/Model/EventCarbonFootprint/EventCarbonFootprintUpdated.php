<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\EventOperations\Domain\Model\Event\DateRange;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EventCarbonFootprintUpdated extends DomainEvent
{
    protected static string $eventName = 'eventCarbonFootprintUpdated';

    public function __construct(
        private EventCarbonFootprintId $eventCarbonFootprintId,
        private DateRange $calculationPeriod,
        private Emissions $totalEmissions,
        private Emissions $emissionsPerAttendee,
        private EventCarbonFootprintStatus $status,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function eventCarbonFootprintId(): EventCarbonFootprintId
    {
        return $this->eventCarbonFootprintId;
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

    public function aggregateRootId(): AggregateRootId
    {
        return $this->eventCarbonFootprintId();
    }

    public function payload(): array
    {
        return [
            'eventCarbonFootprintId' => (string) $this->eventCarbonFootprintId,
            'calculationPeriodStartDate' => $this->calculationPeriod->startDate()?->format(\DateTimeInterface::ATOM),
            'calculationPeriodEndDate' => $this->calculationPeriod->endDate()?->format(\DateTimeInterface::ATOM),
            'totalEmissionsCo2eq' => $this->totalEmissions->co2eq(),
            'totalEmissionsCo2' => $this->totalEmissions->co2(),
            'totalEmissionsCh4' => $this->totalEmissions->ch4(),
            'totalEmissionsN2o' => $this->totalEmissions->n2o(),
            'emissionsPerAttendeeCo2eq' => $this->emissionsPerAttendee->co2eq(),
            'emissionsPerAttendeeCo2' => $this->emissionsPerAttendee->co2(),
            'emissionsPerAttendeeCh4' => $this->emissionsPerAttendee->ch4(),
            'emissionsPerAttendeeN2o' => $this->emissionsPerAttendee->n2o(),
            'status' => $this->status->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $status = EventCarbonFootprintStatus::tryFrom($payload['status']);
        assert(null !== $status);

        return new EventCarbonFootprintUpdated(
            eventCarbonFootprintId: new EventCarbonFootprintId($payload['eventCarbonFootprintId']),
            calculationPeriod: new DateRange(
                startDate: isset($payload['calculationPeriodStartDate']) ? new \DateTimeImmutable($payload['calculationPeriodStartDate']) : null,
                endDate: isset($payload['calculationPeriodEndDate']) ? new \DateTimeImmutable($payload['calculationPeriodEndDate']) : null,
            ),
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
            status: $status,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
