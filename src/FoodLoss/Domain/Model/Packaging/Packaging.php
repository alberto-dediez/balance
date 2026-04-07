<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Packaging;

use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;

class Packaging implements \Stringable
{
    use AggregateRoot;

    /** @var iterable<PackagingPropertyStock>|PackagingPropertyStocks */
    private iterable $stocks = [];

    /** @param array<\App\Shared\Domain\Event\DomainEvent> $events */
    public function __construct(
        private PackagingId $id,
        private string $name = '',
        private PackagingType $packagingType = PackagingType::Reusable,
        private ?AccountId $accountId = null,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): PackagingId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function packagingType(): PackagingType
    {
        return $this->packagingType;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function stocks(): PackagingPropertyStocks
    {
        if ($this->stocks instanceof PackagingPropertyStocks) {
            return $this->stocks;
        }

        $this->stocks = new PackagingPropertyStocks(
            array_values(iterator_to_array($this->stocks)),
        );

        return $this->stocks;
    }

    public function setPropertyStock(PropertyId $propertyId, int $stock): void
    {
        $existing = $this->stocks()->getByPropertyId($propertyId);

        if ($existing instanceof PackagingPropertyStock && $existing->stock() === $stock) {
            return;
        }

        $this->recordApplyAndPublish(
            new PackagingPropertyStockSet(
                packagingId: $this->id(),
                propertyId: $propertyId,
                stock: $stock,
            ),
        );
    }

    public function removePropertyStock(PropertyId $propertyId): void
    {
        if (!$this->stocks()->has($propertyId)) {
            return;
        }

        $this->recordApplyAndPublish(
            new PackagingPropertyStockRemoved(
                packagingId: $this->id(),
                propertyId: $propertyId,
            ),
        );
    }

    public function register(
        string $name,
        PackagingType $packagingType,
        AccountId $accountId,
    ): void {
        $this->recordApplyAndPublish(
            new PackagingRegistered(
                packagingId: $this->id(),
                name: $name,
                packagingType: $packagingType,
                accountId: $accountId,
            ),
        );
    }

    public function update(
        string $name,
        PackagingType $packagingType,
    ): void {
        $this->recordApplyAndPublish(
            new PackagingUpdated(
                packagingId: $this->id(),
                name: $name,
                packagingType: $packagingType,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new PackagingRemoved(
                packagingId: $this->id(),
            ),
        );
    }

    public function applyPackagingRegistered(PackagingRegistered $event): void
    {
        $this->name = $event->name();
        $this->packagingType = $event->packagingType();
        $this->accountId = $event->accountId();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyPackagingUpdated(PackagingUpdated $event): void
    {
        $this->name = $event->name();
        $this->packagingType = $event->packagingType();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyPackagingRemoved(PackagingRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyPackagingPropertyStockSet(PackagingPropertyStockSet $event): void
    {
        $existing = $this->stocks()->getByPropertyId($event->propertyId());

        if ($existing instanceof PackagingPropertyStock) {
            $existing->setStock($event->stock());

            return;
        }

        $this->stocks()->add(new PackagingPropertyStock(
            packaging: $this,
            propertyId: $event->propertyId(),
            stock: $event->stock(),
        ));
    }

    public function applyPackagingPropertyStockRemoved(PackagingPropertyStockRemoved $event): void
    {
        $this->stocks()->removeById($event->propertyId());
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
