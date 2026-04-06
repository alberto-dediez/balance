<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Event\Property;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointFilter;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\Property\Domain\Model\Property\PropertyRemoved;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

final readonly class RemoveCriticalPointsOnPropertyRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private CriticalPointRepository $criticalPointRepository)
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

        $filter = new CriticalPointFilter(propertyId: $event->propertyId(), limit: 0);
        $criticalPoints = $this->criticalPointRepository->findByFilter($filter);

        foreach ($criticalPoints as $criticalPoint) {
            $criticalPoint->remove();
            $this->criticalPointRepository->add($criticalPoint);
        }
    }
}
