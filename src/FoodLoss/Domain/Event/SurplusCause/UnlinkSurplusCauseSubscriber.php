<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Event\SurplusCause;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointFilter;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRemoved;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class UnlinkSurplusCauseSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private CriticalPointRepository $criticalPointRepository)
    {
        $this->register();
    }

    public function handle(DomainEvent $event): void
    {
        if (!$this->isSubscribedTo($event)) {
            throw new \RuntimeException('Incorrect event!');
        }

        assert($event instanceof SurplusCauseRemoved);
        $this->handleSurplusCauseRemoved($event);
    }

    private function handleSurplusCauseRemoved(SurplusCauseRemoved $event): void
    {
        $criticalPoints = $this->criticalPointRepository->findByFilter(
            new CriticalPointFilter(surplusCauseId: $event->surplusCauseId(), limit: 0),
        );

        foreach ($criticalPoints as $criticalPoint) {
            $criticalPoint->unlinkSurplusCause($event->surplusCauseId());
            $this->criticalPointRepository->add($criticalPoint);
        }
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof SurplusCauseRemoved;
    }
}
