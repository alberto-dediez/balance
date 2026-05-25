<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Event\Surplus;

use App\FoodLoss\Domain\Model\Action\Priority;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\FoodLoss\Domain\Model\Surplus\SurplusPreventionAssessed;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

final class LinkActionOnSurplusPreventionAssessedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private SurplusRepository $surplusRepository,
        private CriticalPointRepository $criticalPointRepository,
    ) {
        $this->register();
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof SurplusPreventionAssessed;
    }

    public function handle(DomainEvent $event): void
    {
        \assert($event instanceof SurplusPreventionAssessed);

        if (!$event->canBePrevented() || null === $event->actionId()) {
            return;
        }

        $surplus = $this->surplusRepository->find($event->surplusId());
        if (null === $surplus) {
            return;
        }

        $criticalPoint = $this->criticalPointRepository->find($surplus->criticalPointId());
        if (null === $criticalPoint) {
            return;
        }

        $criticalPoint->linkAction($event->actionId(), Priority::Prevention);
        $criticalPoint->activatePrevention();

        $this->criticalPointRepository->add($criticalPoint);
    }
}
