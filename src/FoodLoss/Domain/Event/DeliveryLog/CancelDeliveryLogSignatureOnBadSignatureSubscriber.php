<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Event\DeliveryLog;

use App\FoodLoss\Application\DeliveryLog\CancelSignatureProcess\CancelSignatureProcessRequest;
use App\FoodLoss\Application\DeliveryLog\CancelSignatureProcess\CancelSignatureProcessService;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestExpired;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRemoved;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

final class CancelDeliveryLogSignatureOnBadSignatureSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private CancelSignatureProcessService $cancelSignatureProcessService,
        private LoggedUser $loggedUser,
    ) {
        $this->register();
    }

    public function isSubscribedTo(DomainEvent $event): bool
    {
        return $event instanceof SignatureRequestExpired || $event instanceof SignatureRequestRemoved;
    }

    public function handle(DomainEvent $event): void
    {
        assert($event instanceof SignatureRequestExpired || $event instanceof SignatureRequestRemoved);

        ($this->cancelSignatureProcessService)(
            new CancelSignatureProcessRequest($event->signatureRequestId()),
            $this->loggedUser->__invoke(),
        );
    }
}
