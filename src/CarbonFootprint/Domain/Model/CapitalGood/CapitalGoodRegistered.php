<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CapitalGood;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CapitalGoodRegistered extends DomainEvent
{
    protected static string $eventName = 'capitalGoodRegistered';

    public function __construct(
        private CapitalGoodId $capitalGoodId,
        private string $name,
        private CapitalGoodCategory $category,
        private Unit $unit,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function capitalGoodId(): CapitalGoodId
    {
        return $this->capitalGoodId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function category(): CapitalGoodCategory
    {
        return $this->category;
    }

    public function unit(): Unit
    {
        return $this->unit;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->capitalGoodId();
    }

    public function payload(): array
    {
        return [
            'capitalGoodId' => (string) $this->capitalGoodId,
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
        $category = CapitalGoodCategory::from($payload['category']);
        $unit = Unit::from($payload['unit']);

        return new CapitalGoodRegistered(
            capitalGoodId: new CapitalGoodId($payload['capitalGoodId']),
            name: $payload['name'],
            category: $category,
            unit: $unit,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
