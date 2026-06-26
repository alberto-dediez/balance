<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CateringItem;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CateringItemRegistered extends DomainEvent
{
    protected static string $eventName = 'cateringItemRegistered';

    public function __construct(
        private CateringItemId $cateringItemId,
        private string $name,
        private CateringItemCategory $category,
        private Unit $unit,
        private ?string $unitWeight = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function cateringItemId(): CateringItemId
    {
        return $this->cateringItemId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function category(): CateringItemCategory
    {
        return $this->category;
    }

    public function unit(): Unit
    {
        return $this->unit;
    }

    public function unitWeight(): ?string
    {
        return $this->unitWeight;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->cateringItemId();
    }

    public function payload(): array
    {
        return [
            'cateringItemId' => (string) $this->cateringItemId,
            'name' => $this->name,
            'category' => $this->category->value,
            'unit' => $this->unit->value,
            'unitWeight' => $this->unitWeight,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $category = CateringItemCategory::from($payload['category']);
        $unit = Unit::from($payload['unit']);

        return new CateringItemRegistered(
            cateringItemId: new CateringItemId($payload['cateringItemId']),
            name: $payload['name'],
            category: $category,
            unit: $unit,
            unitWeight: $payload['unitWeight'] ?? null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
