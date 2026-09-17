<?php

declare(strict_types=1);

namespace App\Bioscore\Domain\Model\BioscoreCatalogItem;

use App\Bioscore\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class BioscoreCatalogItemUpdated extends DomainEvent
{
    protected static string $eventName = 'bioscoreCatalogItemUpdated';

    public function __construct(
        private BioscoreCatalogItemId $itemId,
        private string $name,
        private string $unit,
        private \DateTimeImmutable $seenAt,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function itemId(): BioscoreCatalogItemId
    {
        return $this->itemId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function unit(): string
    {
        return $this->unit;
    }

    public function seenAt(): \DateTimeImmutable
    {
        return $this->seenAt;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->itemId();
    }

    public function payload(): array
    {
        return [
            'itemId' => (string) $this->itemId,
            'name' => $this->name,
            'unit' => $this->unit,
            'seenAt' => $this->seenAt->format(\DateTimeInterface::ATOM),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new BioscoreCatalogItemUpdated(
            itemId: new BioscoreCatalogItemId($payload['itemId']),
            name: $payload['name'],
            unit: $payload['unit'],
            seenAt: new \DateTimeImmutable($payload['seenAt']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
