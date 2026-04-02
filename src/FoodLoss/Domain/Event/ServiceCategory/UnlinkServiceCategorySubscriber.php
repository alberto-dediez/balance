<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Event\ServiceCategory;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointFilter;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRemoved;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

class UnlinkServiceCategorySubscriber implements DomainEventSubscriber
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

        assert($event instanceof ServiceCategoryRemoved);
        $this->handleServiceCategoryRemoved($event);
    }

    private function handleServiceCategoryRemoved(ServiceCategoryRemoved $event): void
    {
        $criticalPoints = $this->criticalPointRepository->findByFilter(
            new CriticalPointFilter(serviceCategoryId: $event->serviceCategoryId(), limit: 0),
        );

        foreach ($criticalPoints as $criticalPoint) {
            $criticalPoint->unlinkServiceCategory($event->serviceCategoryId());
            $this->criticalPointRepository->add($criticalPoint);
        }
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof ServiceCategoryRemoved;
    }
}
