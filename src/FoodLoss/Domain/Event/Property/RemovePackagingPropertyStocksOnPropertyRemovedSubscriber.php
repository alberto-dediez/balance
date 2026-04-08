<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Event\Property;

use App\FoodLoss\Domain\Model\Packaging\PackagingFilter;
use App\FoodLoss\Domain\Model\Packaging\PackagingRepository;
use App\Property\Domain\Model\Property\PropertyRemoved;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

final readonly class RemovePackagingPropertyStocksOnPropertyRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private PackagingRepository $packagingRepository)
    {
        $this->register();
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof PropertyRemoved;
    }

    public function handle(DomainEvent $event): void
    {
        \assert($event instanceof PropertyRemoved);

        $filter = new PackagingFilter(propertyId: $event->propertyId(), limit: 0);
        $packagings = $this->packagingRepository->findByFilter($filter);

        foreach ($packagings as $packaging) {
            $packaging->removePropertyStock($event->propertyId());
            $this->packagingRepository->add($packaging);
        }
    }
}
