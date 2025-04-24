<?php

namespace App\Waste\Domain\Model\WasteManager;

use DateTimeImmutable;
use App\Waste\Domain\DomainEvent;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;
use App\Shared\Domain\Event\DomainEventId;

final class WasteManagerRemoved extends DomainEvent
{
    protected static string $eventName = 'wasteManagerRemoved';

    public function __construct(
        private WasteManagerId $wasteManagerId, 
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function wasteManagerId(): WasteManagerId
    {
        return $this->wasteManagerId;
    }

    public function payload(): ?array
    {
        return [
            'wasteManagerId' => (string) $this->wasteManagerId
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new WasteManagerRemoved(
            wasteManagerId: new WasteManagerId($payload['wasteManagerId']),
            id: $id,
            occurredOn: $occurredOn
        );
    }
}