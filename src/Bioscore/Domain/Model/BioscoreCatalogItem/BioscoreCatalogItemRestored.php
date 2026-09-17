<?php

declare(strict_types=1);

namespace App\Bioscore\Domain\Model\BioscoreCatalogItem;

use App\Bioscore\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class BioscoreCatalogItemRestored extends DomainEvent
{
    protected static string $eventName = 'bioscoreCatalogItemRestored';

    public function __construct(
        private BioscoreCatalogItemId $itemId,
        private \DateTimeImmutable $at,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function itemId(): BioscoreCatalogItemId
    {
        return $this->itemId;
    }

    public function at(): \DateTimeImmutable
    {
        return $this->at;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->itemId();
    }

    public function payload(): array
    {
        return [
            'itemId' => (string) $this->itemId,
            'at' => $this->at->format(\DateTimeInterface::ATOM),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new BioscoreCatalogItemRestored(
            itemId: new BioscoreCatalogItemId($payload['itemId']),
            at: new \DateTimeImmutable($payload['at']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
