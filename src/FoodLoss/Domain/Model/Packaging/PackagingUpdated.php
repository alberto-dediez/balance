<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Packaging;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PackagingUpdated extends DomainEvent
{
    protected static string $eventName = 'packagingUpdated';

    public function __construct(
        private PackagingId $packagingId,
        private string $name,
        private PackagingType $packagingType,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function packagingId(): PackagingId
    {
        return $this->packagingId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function packagingType(): PackagingType
    {
        return $this->packagingType;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->packagingId();
    }

    public function payload(): array
    {
        return [
            'packagingId' => (string) $this->packagingId,
            'name' => $this->name,
            'packagingType' => $this->packagingType->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $packagingType = PackagingType::tryFrom($payload['packagingType']);
        assert(null !== $packagingType);

        return new PackagingUpdated(
            packagingId: new PackagingId($payload['packagingId']),
            name: $payload['name'],
            packagingType: $packagingType,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
