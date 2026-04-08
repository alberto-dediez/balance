<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Event\CriticalPoint;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRemoved;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionFilter;
use App\FoodLoss\Domain\Model\DoggyBagException\DoggyBagExceptionRepository;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

final readonly class RemoveDoggyBagExceptionsOnCriticalPointRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private DoggyBagExceptionRepository $doggyBagExceptionRepository)
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

        $filter = new DoggyBagExceptionFilter(criticalPointId: $event->criticalPointId(), limit: 0);
        $doggyBagExceptions = $this->doggyBagExceptionRepository->findByFilter($filter);

        foreach ($doggyBagExceptions as $doggyBagException) {
            $doggyBagException->remove();
            $this->doggyBagExceptionRepository->add($doggyBagException);
        }
    }
}
