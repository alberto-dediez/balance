<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Event\Property;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogFilter;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\Property\Domain\Model\Property\PropertyRemoved;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

final readonly class RemoveDeliveryLogsOnPropertyRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private DeliveryLogRepository $deliveryLogRepository)
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

        $filter = new DeliveryLogFilter(propertyId: $event->propertyId(), limit: 0);
        $deliveryLogs = $this->deliveryLogRepository->findByFilter($filter);

        foreach ($deliveryLogs as $deliveryLog) {
            $deliveryLog->remove();
            $this->deliveryLogRepository->add($deliveryLog);
        }
    }
}
