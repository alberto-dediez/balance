<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CateringItem;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CateringItemEmissionFactorAdded extends DomainEvent
{
    protected static string $eventName = 'cateringItemEmissionFactorAdded';

    public function __construct(
        private CateringItemId $cateringItemId,
        private CateringItemEmissionFactorId $emissionFactorId,
        private EmissionFactor $newFactor,
        private EmissionFactor $recycledFactor,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function cateringItemId(): CateringItemId
    {
        return $this->cateringItemId;
    }

    public function emissionFactorId(): CateringItemEmissionFactorId
    {
        return $this->emissionFactorId;
    }

    public function newFactor(): EmissionFactor
    {
        return $this->newFactor;
    }

    public function recycledFactor(): EmissionFactor
    {
        return $this->recycledFactor;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->cateringItemId();
    }

    public function payload(): array
    {
        return [
            'cateringItemId' => (string) $this->cateringItemId,
            'emissionFactorId' => (string) $this->emissionFactorId,
            'co2eq' => $this->newFactor->co2eq(),
            'co2' => $this->newFactor->co2(),
            'ch4' => $this->newFactor->ch4(),
            'n2o' => $this->newFactor->n2o(),
            'year' => $this->newFactor->year(),
            'unit' => $this->newFactor->unit()->value,
            'source' => $this->newFactor->source(),
            'recycledCo2eq' => $this->recycledFactor->co2eq(),
            'recycledCo2' => $this->recycledFactor->co2(),
            'recycledCh4' => $this->recycledFactor->ch4(),
            'recycledN2o' => $this->recycledFactor->n2o(),
            'recycledYear' => $this->recycledFactor->year(),
            'recycledUnit' => $this->recycledFactor->unit()->value,
            'recycledSource' => $this->recycledFactor->source(),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $unit = Unit::from($payload['unit']);

        $newFactor = new EmissionFactor(
            emissionFactorGroupId: EmissionFactorGroupId::default(),
            co2eq: $payload['co2eq'],
            co2: $payload['co2'],
            ch4: $payload['ch4'],
            n2o: $payload['n2o'],
            year: $payload['year'],
            unit: $unit,
            source: $payload['source'],
        );

        $recycledFactor = isset($payload['recycledCo2eq'])
            ? new EmissionFactor(
                emissionFactorGroupId: EmissionFactorGroupId::default(),
                co2eq: $payload['recycledCo2eq'],
                co2: $payload['recycledCo2'],
                ch4: $payload['recycledCh4'],
                n2o: $payload['recycledN2o'],
                year: $payload['recycledYear'] ?? $payload['year'],
                unit: Unit::from($payload['recycledUnit'] ?? $payload['unit']),
                source: $payload['recycledSource'] ?? $payload['source'],
            )
            : $newFactor;

        return new CateringItemEmissionFactorAdded(
            cateringItemId: new CateringItemId($payload['cateringItemId']),
            emissionFactorId: isset($payload['emissionFactorId'])
                ? new CateringItemEmissionFactorId($payload['emissionFactorId'])
                : CateringItemEmissionFactorId::generate(),
            newFactor: $newFactor,
            recycledFactor: $recycledFactor,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
