<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Event\DeliveryLog;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestExpired;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRemoved;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

final class CancelDeliveryLogSignatureOnBadSignatureSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private DeliveryLogRepository $deliveryLogRepository)
    {
        $this->register();
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof SignatureRequestExpired || $event instanceof SignatureRequestRemoved;
    }

    public function handle(DomainEvent $event): void
    {
        assert($event instanceof SignatureRequestExpired || $event instanceof SignatureRequestRemoved);

        $deliveryLogs = $this->deliveryLogRepository->findBySignatureRequestId($event->signatureRequestId());

        foreach ($deliveryLogs as $deliveryLog) {
            $deliveryLog->cancelSignatureProcess();
            $this->deliveryLogRepository->add($deliveryLog);
        }
    }
}
