<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PurchaseService;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PurchaseServiceEmissionFactorAdded extends DomainEvent
{
    protected static string $eventName = 'purchaseServiceEmissionFactorAdded';

    public function __construct(
        private PurchaseServiceId $purchaseServiceId,
        private PurchaseServiceEmissionFactorId $emissionFactorId,
        private EmissionFactor $emissionFactor,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function purchaseServiceId(): PurchaseServiceId
    {
        return $this->purchaseServiceId;
    }

    public function emissionFactorId(): PurchaseServiceEmissionFactorId
    {
        return $this->emissionFactorId;
    }

    public function emissionFactor(): EmissionFactor
    {
        return $this->emissionFactor;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->purchaseServiceId();
    }

    public function payload(): array
    {
        return [
            'purchaseServiceId' => (string) $this->purchaseServiceId,
            'emissionFactorId' => (string) $this->emissionFactorId,
            'co2eq' => $this->emissionFactor->co2eq(),
            'co2' => $this->emissionFactor->co2(),
            'ch4' => $this->emissionFactor->ch4(),
            'n2o' => $this->emissionFactor->n2o(),
            'year' => $this->emissionFactor->year(),
            'unit' => $this->emissionFactor->unit()->value,
            'source' => $this->emissionFactor->source(),
            'emissionFactorGroupId' => (string) $this->emissionFactor->emissionFactorGroupId(),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $unit = Unit::from($payload['unit']);

        return new PurchaseServiceEmissionFactorAdded(
            purchaseServiceId: new PurchaseServiceId($payload['purchaseServiceId']),
            emissionFactorId: isset($payload['emissionFactorId'])
                ? new PurchaseServiceEmissionFactorId($payload['emissionFactorId'])
                : PurchaseServiceEmissionFactorId::generate(),
            emissionFactor: new EmissionFactor(
                emissionFactorGroupId: new EmissionFactorGroupId($payload['emissionFactorGroupId']),
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
