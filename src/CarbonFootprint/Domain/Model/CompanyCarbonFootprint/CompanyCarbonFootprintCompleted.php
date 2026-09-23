<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CompanyCarbonFootprintCompleted extends DomainEvent
{
    protected static string $eventName = 'companyCarbonFootprintCompleted';

    public function __construct(
        private CompanyCarbonFootprintId $companyCarbonFootprintId,
        private Emissions $totalEmissions,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function companyCarbonFootprintId(): CompanyCarbonFootprintId
    {
        return $this->companyCarbonFootprintId;
    }

    public function totalEmissions(): Emissions
    {
        return $this->totalEmissions;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->companyCarbonFootprintId;
    }

    public function payload(): array
    {
        return [
            'companyCarbonFootprintId' => (string) $this->companyCarbonFootprintId,
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
            companyCarbonFootprintId: new CompanyCarbonFootprintId($payload['companyCarbonFootprintId']),
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
