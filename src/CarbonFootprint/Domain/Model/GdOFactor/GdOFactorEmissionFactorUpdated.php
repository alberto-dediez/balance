<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\GdOFactor;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class GdOFactorEmissionFactorUpdated extends DomainEvent
{
    protected static string $eventName = 'gdOFactorEmissionFactorUpdated';

    public function __construct(
        private GdOFactorId $gdOFactorId,
        private EmissionFactor $emissionFactor,
        private EmissionFactor $wellToTankFactor,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function gdOFactorId(): GdOFactorId
    {
        return $this->gdOFactorId;
    }

    public function emissionFactor(): EmissionFactor
    {
        return $this->emissionFactor;
    }

    public function wellToTankFactor(): EmissionFactor
    {
        return $this->wellToTankFactor;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->gdOFactorId();
    }

    public function payload(): array
    {
        return [
            'gdOFactorId' => (string) $this->gdOFactorId,
            'co2eq' => $this->emissionFactor->co2eq(),
            'co2' => $this->emissionFactor->co2(),
            'ch4' => $this->emissionFactor->ch4(),
            'n2o' => $this->emissionFactor->n2o(),
            'year' => $this->emissionFactor->year(),
            'unit' => $this->emissionFactor->unit()->value,
            'source' => $this->emissionFactor->source(),
            'wellToTankCo2eq' => $this->wellToTankFactor->co2eq(),
            'wellToTankCo2' => $this->wellToTankFactor->co2(),
            'wellToTankCh4' => $this->wellToTankFactor->ch4(),
            'wellToTankN2o' => $this->wellToTankFactor->n2o(),
            'wellToTankSource' => $this->wellToTankFactor->source(),
            'emissionFactorGroupId' => (string) $this->emissionFactor->emissionFactorGroupId(),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $unit = Unit::from($payload['unit']);
        $emissionFactorGroupId = new EmissionFactorGroupId($payload['emissionFactorGroupId']);

        $wellToTankFactor = isset($payload['wellToTankCo2eq'])
            ? new EmissionFactor(
                co2eq: $payload['wellToTankCo2eq'],
                co2: $payload['wellToTankCo2'],
                ch4: $payload['wellToTankCh4'],
                n2o: $payload['wellToTankN2o'],
                year: $payload['year'],
                emissionFactorGroupId: $emissionFactorGroupId,
                unit: $unit,
                source: $payload['wellToTankSource'] ?? null,
            )
            : EmissionFactor::zeroFor($payload['year'], $emissionFactorGroupId, $unit);

        return new GdOFactorEmissionFactorUpdated(
            gdOFactorId: new GdOFactorId($payload['gdOFactorId']),
            emissionFactor: new EmissionFactor(
                emissionFactorGroupId: $emissionFactorGroupId,
                co2eq: $payload['co2eq'],
                co2: $payload['co2'],
                ch4: $payload['ch4'],
                n2o: $payload['n2o'],
                year: $payload['year'],
                unit: $unit,
                source: $payload['source'],
            ),
            wellToTankFactor: $wellToTankFactor,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
