<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PurchaseService;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PurchaseServiceRegistered extends DomainEvent
{
    protected static string $eventName = 'purchaseServiceRegistered';

    public function __construct(
        private PurchaseServiceId $purchaseServiceId,
        private string $name,
        private PurchaseServiceCategory $category,
        private Unit $unit,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function purchaseServiceId(): PurchaseServiceId
    {
        return $this->purchaseServiceId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function category(): PurchaseServiceCategory
    {
        return $this->category;
    }

    public function unit(): Unit
    {
        return $this->unit;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->purchaseServiceId();
    }

    public function payload(): array
    {
        return [
            'purchaseServiceId' => (string) $this->purchaseServiceId,
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
        $category = PurchaseServiceCategory::from($payload['category']);
        $unit = Unit::from($payload['unit']);

        return new PurchaseServiceRegistered(
            purchaseServiceId: new PurchaseServiceId($payload['purchaseServiceId']),
            name: $payload['name'],
            category: $category,
            unit: $unit,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
