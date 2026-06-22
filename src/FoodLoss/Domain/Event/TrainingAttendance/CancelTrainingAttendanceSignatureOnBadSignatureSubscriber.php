<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Event\TrainingAttendance;

use App\FoodLoss\Application\TrainingAttendance\CancelSignatureProcess\CancelTrainingAttendanceSignatureProcessRequest;
use App\FoodLoss\Application\TrainingAttendance\CancelSignatureProcess\CancelTrainingAttendanceSignatureProcessService;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestExpired;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestRemoved;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

final class CancelTrainingAttendanceSignatureOnBadSignatureSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(
        private readonly CancelTrainingAttendanceSignatureProcessService $cancelSignatureProcessService,
        private readonly LoggedUser $loggedUser,
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
            new CancelTrainingAttendanceSignatureProcessRequest($event->signatureRequestId()),
            $this->loggedUser->__invoke(),
        );
    }
}
