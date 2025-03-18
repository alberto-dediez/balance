<?php

namespace App\Waste\Domain\Model\Waste;

use DateTimeImmutable;
use App\Waste\Domain\DomainEvent;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Shared\Domain\Event\DomainEventId;

final class WasteRemoved extends DomainEvent
{
    protected static string $eventName = 'wasteRemoved';

    public function __construct(
        private WasteId $wasteId, 
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function wasteId(): WasteId
    {
        return $this->wasteId;
    }

    public function payload(): ?array
    {
        return [
            'wasteId' => (string) $this->wasteId
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new WasteRemoved(
            wasteId: new WasteId($payload['wasteId']),
            id: $id,
            occurredOn: $occurredOn
        );
    }
}