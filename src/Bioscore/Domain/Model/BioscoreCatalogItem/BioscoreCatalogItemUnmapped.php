<?php

declare(strict_types=1);

namespace App\Bioscore\Domain\Model\BioscoreCatalogItem;

use App\Bioscore\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class BioscoreCatalogItemUnmapped extends DomainEvent
{
    protected static string $eventName = 'bioscoreCatalogItemUnmapped';

    public function __construct(
        private BioscoreCatalogItemId $itemId,
        private ?string $previousTargetId = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function itemId(): BioscoreCatalogItemId
    {
        return $this->itemId;
    }

    public function previousTargetId(): ?string
    {
        return $this->previousTargetId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->itemId();
    }

    public function payload(): array
    {
        return [
            'itemId' => (string) $this->itemId,
            'previousTargetId' => $this->previousTargetId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new BioscoreCatalogItemUnmapped(
            itemId: new BioscoreCatalogItemId($payload['itemId']),
            previousTargetId: $payload['previousTargetId'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
