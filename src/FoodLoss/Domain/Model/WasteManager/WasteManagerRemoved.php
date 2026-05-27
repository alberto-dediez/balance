<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\WasteManager;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class WasteManagerRemoved extends DomainEvent
{
    protected static string $eventName = 'wasteManagerRemoved';

    public function __construct(
        private WasteManagerId $wasteManagerId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function wasteManagerId(): WasteManagerId
    {
        return $this->wasteManagerId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->wasteManagerId();
    }

    public function payload(): array
    {
        return [
            'wasteManagerId' => (string) $this->wasteManagerId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new WasteManagerRemoved(
            wasteManagerId: new WasteManagerId($payload['wasteManagerId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
