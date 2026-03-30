<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\WasteManager;

use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Waste\Domain\DomainEvent;

final class WasteManagerUpdated extends DomainEvent
{
    protected static string $eventName = 'wasteManagerUpdated';

    public function __construct(
        private readonly WasteManagerId $wasteManagerId,
        private readonly string $name,
        private readonly bool $isPublic,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
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

    public function aggregateRootId(): AggregateRootId
    {
        return $this->wasteManagerId;
    }

    public function payload(): array
    {
        return [
            'wasteManagerId' => (string) $this->wasteManagerId,
            'name' => $this->name,
            'isPublic' => $this->isPublic,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new WasteManagerUpdated(
            wasteManagerId: new WasteManagerId($payload['wasteManagerId']),
            name: $payload['name'],
            isPublic: $payload['isPublic'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
