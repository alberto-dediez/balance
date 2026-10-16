<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EconomicSector;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EconomicSectorUpdated extends DomainEvent
{
    protected static string $eventName = 'economicSectorUpdated';

    public function __construct(
        private EconomicSectorId $economicSectorId,
        private string $name,
        private Unit $unit,
        private ?string $classificationCode,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function economicSectorId(): EconomicSectorId
    {
        return $this->economicSectorId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function unit(): Unit
    {
        return $this->unit;
    }

    public function classificationCode(): ?string
    {
        return $this->classificationCode;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->economicSectorId();
    }

    public function payload(): array
    {
        return [
            'economicSectorId' => (string) $this->economicSectorId,
            'name' => $this->name,
            'unit' => $this->unit->value,
            'classificationCode' => $this->classificationCode,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $unit = Unit::from($payload['unit']);

        return new EconomicSectorUpdated(
            economicSectorId: new EconomicSectorId($payload['economicSectorId']),
            name: $payload['name'],
            unit: $unit,
            classificationCode: $payload['classificationCode'] ?? null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
