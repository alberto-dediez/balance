<?php

declare(strict_types=1);

namespace App\Bioscore\Domain\Model\BioscoreCatalogItem;

use App\Bioscore\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class BioscoreCatalogItemRegistered extends DomainEvent
{
    protected static string $eventName = 'bioscoreCatalogItemRegistered';

    public function __construct(
        private BioscoreCatalogItemId $itemId,
        private BioscoreCatalog $catalog,
        private int $bioscoreId,
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

    public function catalog(): BioscoreCatalog
    {
        return $this->catalog;
    }

    public function bioscoreId(): int
    {
        return $this->bioscoreId;
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
            'catalog' => $this->catalog->value,
            'bioscoreId' => $this->bioscoreId,
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
        return new BioscoreCatalogItemRegistered(
            itemId: new BioscoreCatalogItemId($payload['itemId']),
            catalog: BioscoreCatalog::from($payload['catalog']),
            bioscoreId: (int) $payload['bioscoreId'],
            name: $payload['name'],
            unit: $payload['unit'],
            seenAt: new \DateTimeImmutable($payload['seenAt']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
