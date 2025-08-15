<?php

namespace App\Waste\Domain\Model\WasteManager;

use DateTimeImmutable;
use App\Waste\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Waste\Domain\Model\WasteManager\WasteManagerId;

final class WasteManagerUpdated extends DomainEvent
{
    protected static string $eventName = 'wasteManagerUpdated';

    public function __construct(
        private WasteManagerId $wasteManagerId, 
        private string $name,
        private bool $isPublic,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function wasteManagerId(): WasteManagerId
    {
        return $this->wasteManagerId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function aggregateRootId(): ?AggregateRootId
    {
        return $this->wasteManagerId();
    }

    public function payload(): ?array
    {
        return [
            'wasteManagerId' => (string) $this->wasteManagerId,
            'name' => $this->name,
            'isPublic' => $this->isPublic
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new WasteManagerUpdated(
            wasteManagerId: new WasteManagerId($payload['wasteManagerId']), 
            name: $payload['name'],
            isPublic: $payload['isPublic'],
            id: $id,
            occurredOn: $occurredOn
        );
    }
}