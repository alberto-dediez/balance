<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Packaging;

use App\FoodLoss\Domain\DomainEvent;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PackagingPropertyStockRemoved extends DomainEvent
{
    protected static string $eventName = 'packagingPropertyStockRemoved';

    public function __construct(
        private readonly PackagingId $packagingId,
        private readonly PropertyId $propertyId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function packagingId(): PackagingId
    {
        return $this->packagingId;
    }

    public function propertyId(): PropertyId
    {
        return $this->propertyId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->packagingId;
    }

    public function payload(): array
    {
        return [
            'packagingId' => (string) $this->packagingId,
            'propertyId' => (string) $this->propertyId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new PackagingPropertyStockRemoved(
            packagingId: new PackagingId($payload['packagingId']),
            propertyId: new PropertyId($payload['propertyId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
