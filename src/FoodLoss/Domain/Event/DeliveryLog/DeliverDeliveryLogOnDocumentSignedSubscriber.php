<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Event\DeliveryLog;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\Security\Domain\Model\SignatureRequest\DocumentSigned;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

final class DeliverDeliveryLogOnDocumentSignedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private DeliveryLogRepository $deliveryLogRepository)
    {
        $this->register();
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof DocumentSigned;
    }

    public function handle(DomainEvent $event): void
    {
        assert($event instanceof DocumentSigned);

        $deliveryLogs = $this->deliveryLogRepository->findBySignatureRequestId($event->signatureRequestId());

        foreach ($deliveryLogs as $deliveryLog) {
            $deliveryLog->endSignatureProcess();
            $this->deliveryLogRepository->add($deliveryLog);
        }
    }
}
