<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PurchaseService;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PurchaseServiceEmissionFactorRemoved extends DomainEvent
{
    protected static string $eventName = 'purchaseServiceEmissionFactorRemoved';

    public function __construct(
        private PurchaseServiceId $purchaseServiceId,
        private int $year,
        private EmissionFactorGroupId $emissionFactorGroupId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function purchaseServiceId(): PurchaseServiceId
    {
        return $this->purchaseServiceId;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function emissionFactorGroupId(): EmissionFactorGroupId
    {
        return $this->emissionFactorGroupId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->purchaseServiceId();
    }

    public function payload(): array
    {
        return [
            'purchaseServiceId' => (string) $this->purchaseServiceId,
            'year' => $this->year,
            'emissionFactorGroupId' => (string) $this->emissionFactorGroupId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new PurchaseServiceEmissionFactorRemoved(
            purchaseServiceId: new PurchaseServiceId($payload['purchaseServiceId']),
            year: $payload['year'],
            emissionFactorGroupId: new EmissionFactorGroupId($payload['emissionFactorGroupId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
