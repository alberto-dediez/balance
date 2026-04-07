<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Event\Property;

use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanFilter;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Property\Domain\Model\Property\PropertyRemoved;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

final readonly class UnlinkPreventionPlanOnPropertyRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private PreventionPlanRepository $preventionPlanRepository)
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

        $filter = new PreventionPlanFilter(propertyId: $event->propertyId(), limit: 0);
        $preventionPlans = $this->preventionPlanRepository->findByFilter($filter);

        foreach ($preventionPlans as $preventionPlan) {
            $propertyIds = $preventionPlan->propertyIds();

            if ([] === $propertyIds) {
                continue;
            }

            if (1 === count($propertyIds)) {
                $preventionPlan->remove();
            } else {
                $preventionPlan->unlinkProperty($event->propertyId());
            }

            $this->preventionPlanRepository->add($preventionPlan);
        }
    }
}
