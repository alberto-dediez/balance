<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Event\Action;

use App\FoodLoss\Domain\Model\Action\ActionRemoved;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointFilter;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

final class UnlinkActionSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private CriticalPointRepository $criticalPointRepository)
    {
        $this->register();
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof ActionRemoved;
    }

    public function handle(DomainEvent $event): void
    {
        \assert($event instanceof ActionRemoved);

        $this->handleActionRemoved($event);
    }

    private function handleActionRemoved(ActionRemoved $event): void
    {
        $filter = new CriticalPointFilter();
        $filter->actionId = $event->actionId();
        $filter->limit = 0;

        $criticalPoints = $this->criticalPointRepository->findByFilter($filter);

        foreach ($criticalPoints as $criticalPoint) {
            $criticalPoint->unlinkAction($event->actionId());
            $this->criticalPointRepository->add($criticalPoint);
        }
    }
}
