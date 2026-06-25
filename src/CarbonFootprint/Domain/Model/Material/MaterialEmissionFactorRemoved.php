<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Material;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MaterialEmissionFactorRemoved extends DomainEvent
{
    protected static string $eventName = 'materialEmissionFactorRemoved';

    public function __construct(
        private MaterialId $materialId,
        private int $year,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function materialId(): MaterialId
    {
        return $this->materialId;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->materialId();
    }

    public function payload(): array
    {
        return [
            'materialId' => (string) $this->materialId,
            'year' => $this->year,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MaterialEmissionFactorRemoved(
            materialId: new MaterialId($payload['materialId']),
            year: $payload['year'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
