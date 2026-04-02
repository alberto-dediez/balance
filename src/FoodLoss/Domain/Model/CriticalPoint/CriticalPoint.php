<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;

class CriticalPoint implements \Stringable
{
    use AggregateRoot;

    /** @param array<\App\Shared\Domain\Event\DomainEvent> $events */
    public function __construct(
        private CriticalPointId $id,
        private string $name = '',
        private ?PropertyId $propertyId = null,
        private ?AccountId $accountId = null,
        private SupplyChainStage $supplyChainStage = SupplyChainStage::Receiving,
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $removedAt = null,
        /** @var iterable<CriticalPointServiceCategoryLink>|CriticalPointServiceCategories */
        private iterable $serviceCategories = [],
    ) {
        $this->events = $events;
    }

    public function id(): CriticalPointId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function propertyId(): ?PropertyId
    {
        return $this->propertyId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function supplyChainStage(): SupplyChainStage
    {
        return $this->supplyChainStage;
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

    /**
     * @param ServiceCategoryId[] $serviceCategoryIds
     */
    public function register(
        string $name,
        PropertyId $propertyId,
        AccountId $accountId,
        SupplyChainStage $supplyChainStage,
        array $serviceCategoryIds = [],
    ): void {
        $this->recordApplyAndPublish(
            new CriticalPointRegistered(
                criticalPointId: $this->id(),
                name: $name,
                propertyId: $propertyId,
                accountId: $accountId,
                supplyChainStage: $supplyChainStage,
            ),
        );

        $this->syncServiceCategories($serviceCategoryIds);
    }

    /**
     * @param ServiceCategoryId[] $serviceCategoryIds
     */
    public function update(
        string $name,
        PropertyId $propertyId,
        SupplyChainStage $supplyChainStage,
        array $serviceCategoryIds = [],
    ): void {
        $this->recordApplyAndPublish(
            new CriticalPointUpdated(
                criticalPointId: $this->id(),
                name: $name,
                propertyId: $propertyId,
                supplyChainStage: $supplyChainStage,
            ),
        );

        $this->syncServiceCategories($serviceCategoryIds);
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new CriticalPointRemoved(
                criticalPointId: $this->id(),
            ),
        );
    }

    public function serviceCategories(): CriticalPointServiceCategories
    {
        if ($this->serviceCategories instanceof CriticalPointServiceCategories) {
            return $this->serviceCategories;
        }

        $this->serviceCategories = new CriticalPointServiceCategories(
            array_values(iterator_to_array($this->serviceCategories)),
        );

        return $this->serviceCategories;
    }

    /**
     * @return ServiceCategoryId[]
     */
    public function serviceCategoryIds(): array
    {
        return $this->serviceCategories()->ids();
    }

    public function linkServiceCategory(ServiceCategoryId $serviceCategoryId): void
    {
        if ($this->serviceCategories()->has($serviceCategoryId)) {
            return;
        }

        $this->recordApplyAndPublish(
            new CriticalPointServiceCategoryLinked(
                criticalPointId: $this->id(),
                serviceCategoryId: $serviceCategoryId,
            ),
        );
    }

    public function unlinkServiceCategory(ServiceCategoryId $serviceCategoryId): void
    {
        if (!$this->serviceCategories()->has($serviceCategoryId)) {
            return;
        }

        $this->recordApplyAndPublish(
            new CriticalPointServiceCategoryUnlinked(
                criticalPointId: $this->id(),
                serviceCategoryId: $serviceCategoryId,
            ),
        );
    }

    /**
     * @param ServiceCategoryId[] $newServiceCategoryIds
     */
    private function syncServiceCategories(array $newServiceCategoryIds): void
    {
        $current = $this->serviceCategories();

        // Unlink categories that are no longer present
        foreach ($current->ids() as $currentId) {
            $found = false;
            foreach ($newServiceCategoryIds as $newId) {
                if ($currentId->equals($newId)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $this->unlinkServiceCategory($currentId);
            }
        }

        // Link new categories
        foreach ($newServiceCategoryIds as $newId) {
            if (!$current->has($newId)) {
                $this->linkServiceCategory($newId);
            }
        }
    }

    public function applyCriticalPointRegistered(CriticalPointRegistered $event): void
    {
        $this->name = $event->name();
        $this->propertyId = $event->propertyId();
        $this->accountId = $event->accountId();
        $this->supplyChainStage = $event->supplyChainStage();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyCriticalPointUpdated(CriticalPointUpdated $event): void
    {
        $this->name = $event->name();
        $this->propertyId = $event->propertyId();
        $this->supplyChainStage = $event->supplyChainStage();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyCriticalPointRemoved(CriticalPointRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyCriticalPointServiceCategoryLinked(CriticalPointServiceCategoryLinked $event): void
    {
        $link = new CriticalPointServiceCategoryLink(
            criticalPoint: $this,
            serviceCategoryId: $event->serviceCategoryId(),
        );

        $this->serviceCategories()->add($link);
    }

    public function applyCriticalPointServiceCategoryUnlinked(CriticalPointServiceCategoryUnlinked $event): void
    {
        $this->serviceCategories()->removeById($event->serviceCategoryId());
    }

    public function __toString(): string
    {
        return (string) $this->name();
    }
}
