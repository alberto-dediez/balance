<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Event\Surplus;

use App\FoodLoss\Application\DeliveryLog\Remove\RemoveDeliveryLogRequest;
use App\FoodLoss\Application\DeliveryLog\Remove\RemoveDeliveryLogService;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\FoodLoss\Domain\Model\Surplus\SurplusHumanDonationUnassessed;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

final class RemoveDeliveryLogOnSurplusHumanDonationUnassessedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private DeliveryLogRepository $deliveryLogRepository,
        private RemoveDeliveryLogService $removeDeliveryLogService,
        private LoggedUser $loggedUser,
    ) {
        $this->register();
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof SurplusHumanDonationUnassessed;
    }

    public function handle(DomainEvent $event): void
    {
        \assert($event instanceof SurplusHumanDonationUnassessed);

        $existingDeliveryLog = $this->deliveryLogRepository->findBySurplusId($event->surplusId());

        if (null === $existingDeliveryLog) {
            return;
        }

        ($this->removeDeliveryLogService)(
            new RemoveDeliveryLogRequest(
                deliveryLogId: $existingDeliveryLog->id(),
            ),
            $this->loggedUser->__invoke(),
        );
    }
}
