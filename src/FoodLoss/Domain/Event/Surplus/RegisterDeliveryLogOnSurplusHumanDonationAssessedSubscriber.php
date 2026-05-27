<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Event\Surplus;

use App\FoodLoss\Application\DeliveryLog\Register\RegisterDeliveryLogRequest;
use App\FoodLoss\Application\DeliveryLog\Register\RegisterDeliveryLogService;
use App\FoodLoss\Application\DeliveryLog\Remove\RemoveDeliveryLogRequest;
use App\FoodLoss\Application\DeliveryLog\Remove\RemoveDeliveryLogService;
use App\FoodLoss\Application\DeliveryLog\Update\UpdateDeliveryLogRequest;
use App\FoodLoss\Application\DeliveryLog\Update\UpdateDeliveryLogService;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\FoodLoss\Domain\Model\Surplus\SurplusHumanDonationAssessed;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

final class RegisterDeliveryLogOnSurplusHumanDonationAssessedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private SurplusRepository $surplusRepository,
        private CriticalPointRepository $criticalPointRepository,
        private DeliveryLogRepository $deliveryLogRepository,
        private RegisterDeliveryLogService $registerDeliveryLogService,
        private UpdateDeliveryLogService $updateDeliveryLogService,
        private RemoveDeliveryLogService $removeDeliveryLogService,
        private LoggedUser $loggedUser,
    ) {
        $this->register();
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof SurplusHumanDonationAssessed;
    }

    public function handle(DomainEvent $event): void
    {
        \assert($event instanceof SurplusHumanDonationAssessed);

        $existingDeliveryLog = $this->deliveryLogRepository->findBySurplusId($event->surplusId());
        $donationDate = $event->donationDate();
        $recipientOrganizationId = $event->recipientOrganizationId();

        $shouldCreateOrUpdate = $event->canBeHumanDonated()
            && $event->createDeliveryLog()
            && null !== $donationDate
            && null !== $recipientOrganizationId;

        if (null !== $existingDeliveryLog && !$shouldCreateOrUpdate) {
            ($this->removeDeliveryLogService)(
                new RemoveDeliveryLogRequest(
                    deliveryLogId: $existingDeliveryLog->id(),
                ),
                $this->loggedUser->__invoke(),
            );

            return;
        }

        if (!$shouldCreateOrUpdate) {
            return;
        }

        $surplus = $this->surplusRepository->find($event->surplusId());
        if (null === $surplus) {
            return;
        }

        $criticalPoint = $this->criticalPointRepository->find($surplus->criticalPointId());
        if (null === $criticalPoint || null === $criticalPoint->propertyId()) {
            return;
        }

        if (null !== $existingDeliveryLog) {
            ($this->updateDeliveryLogService)(
                new UpdateDeliveryLogRequest(
                    deliveryLogId: $existingDeliveryLog->id(),
                    date: $donationDate,
                    quantity: $surplus->quantity(),
                    unit: $surplus->unit(),
                    productType: $surplus->foodType(),
                    batch: $event->batch() ?? '',
                    recipientOrganizationId: $recipientOrganizationId,
                    propertyId: $criticalPoint->propertyId(),
                ),
                $this->loggedUser->__invoke(),
            );

            return;
        }

        ($this->registerDeliveryLogService)(
            new RegisterDeliveryLogRequest(
                date: $donationDate,
                quantity: $surplus->quantity(),
                unit: $surplus->unit(),
                productType: $surplus->foodType(),
                batch: $event->batch() ?? '',
                recipientOrganizationId: $recipientOrganizationId,
                propertyId: $criticalPoint->propertyId(),
                accountId: $surplus->accountId(),
                surplusId: $event->surplusId(),
            ),
            $this->loggedUser->__invoke(),
        );
    }
}
