<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\GdOFactor;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class GdOFactorEmissionFactorAdded extends DomainEvent
{
    protected static string $eventName = 'gdOFactorEmissionFactorAdded';

    public function __construct(
        private GdOFactorId $gdOFactorId,
        private GdOFactorEmissionFactorId $emissionFactorId,
        private EmissionFactor $emissionFactor,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function gdOFactorId(): GdOFactorId
    {
        return $this->gdOFactorId;
    }

    public function emissionFactorId(): GdOFactorEmissionFactorId
    {
        return $this->emissionFactorId;
    }

    public function emissionFactor(): EmissionFactor
    {
        return $this->emissionFactor;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->gdOFactorId();
    }

    public function payload(): array
    {
        return [
            'gdOFactorId' => (string) $this->gdOFactorId,
            'emissionFactorId' => (string) $this->emissionFactorId,
            'co2eq' => $this->emissionFactor->co2eq(),
            'co2' => $this->emissionFactor->co2(),
            'ch4' => $this->emissionFactor->ch4(),
            'n2o' => $this->emissionFactor->n2o(),
            'year' => $this->emissionFactor->year(),
            'unit' => $this->emissionFactor->unit()->value,
            'source' => $this->emissionFactor->source(),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $unit = Unit::from($payload['unit']);

        return new GdOFactorEmissionFactorAdded(
            gdOFactorId: new GdOFactorId($payload['gdOFactorId']),
            emissionFactorId: isset($payload['emissionFactorId'])
                ? new GdOFactorEmissionFactorId($payload['emissionFactorId'])
                : GdOFactorEmissionFactorId::generate(),
            emissionFactor: new EmissionFactor(
                emissionFactorGroupId: EmissionFactorGroupId::default(),
                co2eq: $payload['co2eq'],
                co2: $payload['co2'],
                ch4: $payload['ch4'],
                n2o: $payload['n2o'],
                year: $payload['year'],
                unit: $unit,
                source: $payload['source'],
            ),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
