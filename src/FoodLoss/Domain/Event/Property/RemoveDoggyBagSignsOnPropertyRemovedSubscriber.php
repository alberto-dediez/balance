<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Event\Property;

use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignFilter;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepository;
use App\Property\Domain\Model\Property\PropertyRemoved;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

final readonly class RemoveDoggyBagSignsOnPropertyRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private DoggyBagSignRepository $doggyBagSignRepository)
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

        $filter = new DoggyBagSignFilter(propertyId: $event->propertyId(), limit: 0);
        $doggyBagSigns = $this->doggyBagSignRepository->findByFilter($filter);

        foreach ($doggyBagSigns as $doggyBagSign) {
            $doggyBagSign->remove();
            $this->doggyBagSignRepository->add($doggyBagSign);
        }
    }
}
