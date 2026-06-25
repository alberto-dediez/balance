<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Material;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MaterialRegistered extends DomainEvent
{
    protected static string $eventName = 'materialRegistered';

    public function __construct(
        private MaterialId $materialId,
        private string $name,
        private MaterialCategory $category,
        private Unit $unit,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function materialId(): MaterialId
    {
        return $this->materialId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function category(): MaterialCategory
    {
        return $this->category;
    }

    public function unit(): Unit
    {
        return $this->unit;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->materialId();
    }

    public function payload(): array
    {
        return [
            'materialId' => (string) $this->materialId,
            'name' => $this->name,
            'category' => $this->category->value,
            'unit' => $this->unit->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $category = MaterialCategory::from($payload['category']);
        $unit = Unit::from($payload['unit']);

        return new MaterialRegistered(
            materialId: new MaterialId($payload['materialId']),
            name: $payload['name'],
            category: $category,
            unit: $unit,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
