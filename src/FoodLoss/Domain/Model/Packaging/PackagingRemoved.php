<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Packaging;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PackagingRemoved extends DomainEvent
{
    protected static string $eventName = 'packagingRemoved';

    public function __construct(
        private PackagingId $packagingId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function packagingId(): PackagingId
    {
        return $this->packagingId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->packagingId();
    }

    public function payload(): array
    {
        return [
            'packagingId' => (string) $this->packagingId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new PackagingRemoved(
            packagingId: new PackagingId($payload['packagingId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
