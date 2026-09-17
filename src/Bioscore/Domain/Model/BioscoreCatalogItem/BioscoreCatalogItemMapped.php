<?php

declare(strict_types=1);

namespace App\Bioscore\Domain\Model\BioscoreCatalogItem;

use App\Bioscore\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class BioscoreCatalogItemMapped extends DomainEvent
{
    protected static string $eventName = 'bioscoreCatalogItemMapped';

    public function __construct(
        private BioscoreCatalogItemId $itemId,
        private string $targetId,
        private bool $automatic,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function itemId(): BioscoreCatalogItemId
    {
        return $this->itemId;
    }

    public function targetId(): string
    {
        return $this->targetId;
    }

    public function isAutomatic(): bool
    {
        return $this->automatic;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->itemId();
    }

    public function payload(): array
    {
        return [
            'itemId' => (string) $this->itemId,
            'targetId' => $this->targetId,
            'automatic' => $this->automatic,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new BioscoreCatalogItemMapped(
            itemId: new BioscoreCatalogItemId($payload['itemId']),
            targetId: $payload['targetId'],
            automatic: (bool) $payload['automatic'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
