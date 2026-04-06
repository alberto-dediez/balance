<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Event\RecipientOrganization;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogFilter;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationRemoved;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

final class RemoveDeliveryLogsOnRecipientOrganizationRemovedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private readonly DeliveryLogRepository $deliveryLogRepository)
    {
        $this->register();
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof RecipientOrganizationRemoved;
    }

    public function handle(DomainEvent $event): void
    {
        \assert($event instanceof RecipientOrganizationRemoved);

        $filter = new DeliveryLogFilter(recipientOrganizationId: $event->recipientOrganizationId(), limit: 0);
        $deliveryLogs = $this->deliveryLogRepository->findByFilter($filter);

        foreach ($deliveryLogs as $deliveryLog) {
            $deliveryLog->remove();
            $this->deliveryLogRepository->add($deliveryLog);
        }
    }
}
