<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Event\CriticalPoint;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRemoved;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanFilter;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

final readonly class RemoveProposedActionsOnCriticalPointRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private PreventionPlanRepository $preventionPlanRepository)
    {
        $this->register();
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof CriticalPointRemoved;
    }

    public function handle(DomainEvent $event): void
    {
        \assert($event instanceof CriticalPointRemoved);

        $filter = new PreventionPlanFilter(criticalPointId: $event->criticalPointId(), limit: 0);
        $preventionPlans = $this->preventionPlanRepository->findByFilter($filter);

        foreach ($preventionPlans as $preventionPlan) {
            $preventionPlan->removeProposedActionsByCriticalPoint($event->criticalPointId());
            $this->preventionPlanRepository->add($preventionPlan);
        }
    }
}
