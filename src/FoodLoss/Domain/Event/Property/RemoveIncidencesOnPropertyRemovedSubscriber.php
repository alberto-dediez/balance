<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Event\Property;

use App\FoodLoss\Domain\Model\Incidence\IncidenceFilter;
use App\FoodLoss\Domain\Model\Incidence\IncidenceRepository;
use App\Property\Domain\Model\Property\PropertyRemoved;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

final readonly class RemoveIncidencesOnPropertyRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private IncidenceRepository $incidenceRepository)
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

        $filter = new IncidenceFilter(propertyId: $event->propertyId(), limit: 0);
        $incidences = $this->incidenceRepository->findByFilter($filter);

        foreach ($incidences as $incidence) {
            $incidence->remove();
            $this->incidenceRepository->add($incidence);
        }
    }
}
