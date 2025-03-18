<?php

namespace App\Waste\Domain\Model\Waste;

use DateTimeImmutable;
use App\Waste\Domain\DomainEvent;
use App\Waste\Domain\Model\Waste\WasteId;
use App\Shared\Domain\Event\DomainEventId;

final class WasteRegistered extends DomainEvent
{
    protected static string $eventName = 'wasteRegistered';

    public function __construct(
        private WasteId $wasteId, 
        private string $name, 
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

    public function payload(): ?array
    {
        return [
            'wasteId' => (string) $this->wasteId,
            'name' => $this->name
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new WasteRegistered(
            wasteId: new WasteId($payload['wasteId']), 
            name: $payload['name'],
            id: $id,
            occurredOn: $occurredOn
        );
    }
}