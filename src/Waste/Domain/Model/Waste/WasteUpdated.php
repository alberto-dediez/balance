<?php

namespace App\Waste\Domain\Model\Waste;

use DateTimeImmutable;
use App\Waste\Domain\DomainEvent;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class WasteUpdated extends DomainEvent
{
    protected static string $eventName = 'wasteUpdated';

    public function __construct(
        private readonly WasteId $wasteId,
        private readonly string $name,
        private readonly bool $isDangerous = false,
        private readonly string $density = '0.00',
        private readonly string $color = '#000000',
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function wasteId(): WasteId
    {
        return $this->wasteId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isDangerous(): bool
    {
        return $this->isDangerous;
    }

    public function density(): string
    {
        return $this->density;
    }

    public function color(): string
    {
        return $this->color;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->wasteId;
    }

    public function payload(): array
    {
        return [
            'wasteId' => (string) $this->wasteId,
            'name' => $this->name,
            'isDangerous' => $this->isDangerous,
            'density' => $this->density,
            'color' => $this->color
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        array $payload = []
    ): static {
        return new WasteUpdated(
            wasteId: new WasteId($payload['wasteId']),
            name: $payload['name'],
            isDangerous: $payload['isDangerous'] ?? false,
            density: $payload['density'] ?? '0',
            color: $payload['color'] ?? '#c3d300',
            id: $id,
            occurredOn: $occurredOn
        );
    }
}
