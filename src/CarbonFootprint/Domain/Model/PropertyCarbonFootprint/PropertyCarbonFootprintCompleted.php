<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PropertyCarbonFootprintCompleted extends DomainEvent
{
    protected static string $eventName = 'propertyCarbonFootprintCompleted';

    public function __construct(
        private PropertyCarbonFootprintId $propertyCarbonFootprintId,
        private Emissions $totalEmissions,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function propertyCarbonFootprintId(): PropertyCarbonFootprintId
    {
        return $this->propertyCarbonFootprintId;
    }

    public function totalEmissions(): Emissions
    {
        return $this->totalEmissions;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->propertyCarbonFootprintId;
    }

    public function payload(): array
    {
        return [
            'propertyCarbonFootprintId' => (string) $this->propertyCarbonFootprintId,
            'totalEmissionsCo2eq' => $this->totalEmissions->co2eq(),
            'totalEmissionsCo2' => $this->totalEmissions->co2(),
            'totalEmissionsCh4' => $this->totalEmissions->ch4(),
            'totalEmissionsN2o' => $this->totalEmissions->n2o(),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            propertyCarbonFootprintId: new PropertyCarbonFootprintId($payload['propertyCarbonFootprintId']),
            totalEmissions: new Emissions(
                co2eq: $payload['totalEmissionsCo2eq'] ?? '0',
                co2: $payload['totalEmissionsCo2'] ?? '0',
                ch4: $payload['totalEmissionsCh4'] ?? '0',
                n2o: $payload['totalEmissionsN2o'] ?? '0',
            ),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
