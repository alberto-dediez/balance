<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OvernightStay;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class OvernightStayEmissionFactorAdded extends DomainEvent
{
    protected static string $eventName = 'overnightStayEmissionFactorAdded';

    public function __construct(
        private OvernightStayId $overnightStayId,
        private OvernightStayEmissionFactorId $emissionFactorId,
        private EmissionFactor $emissionFactor,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function overnightStayId(): OvernightStayId
    {
        return $this->overnightStayId;
    }

    public function emissionFactorId(): OvernightStayEmissionFactorId
    {
        return $this->emissionFactorId;
    }

    public function emissionFactor(): EmissionFactor
    {
        return $this->emissionFactor;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->overnightStayId();
    }

    public function payload(): array
    {
        return [
            'overnightStayId' => (string) $this->overnightStayId,
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

        return new OvernightStayEmissionFactorAdded(
            overnightStayId: new OvernightStayId($payload['overnightStayId']),
            emissionFactorId: isset($payload['emissionFactorId'])
                ? new OvernightStayEmissionFactorId($payload['emissionFactorId'])
                : OvernightStayEmissionFactorId::generate(),
            emissionFactor: new EmissionFactor(
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
