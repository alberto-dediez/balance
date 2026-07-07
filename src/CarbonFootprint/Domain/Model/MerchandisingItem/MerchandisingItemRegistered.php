<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\MerchandisingItem;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MerchandisingItemRegistered extends DomainEvent
{
    protected static string $eventName = 'merchandisingItemRegistered';

    public function __construct(
        private MerchandisingItemId $merchandisingItemId,
        private string $name,
        private MerchandisingItemCategory $category,
        private Unit $unit,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function merchandisingItemId(): MerchandisingItemId
    {
        return $this->merchandisingItemId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function category(): MerchandisingItemCategory
    {
        return $this->category;
    }

    public function unit(): Unit
    {
        return $this->unit;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->merchandisingItemId();
    }

    public function payload(): array
    {
        return [
            'merchandisingItemId' => (string) $this->merchandisingItemId,
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
        $category = MerchandisingItemCategory::from($payload['category']);
        $unit = Unit::from($payload['unit']);

        return new MerchandisingItemRegistered(
            merchandisingItemId: new MerchandisingItemId($payload['merchandisingItemId']),
            name: $payload['name'],
            category: $category,
            unit: $unit,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
