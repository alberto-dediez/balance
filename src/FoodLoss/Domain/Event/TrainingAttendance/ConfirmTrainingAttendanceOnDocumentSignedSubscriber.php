<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Event\TrainingAttendance;

use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingAttendanceRepository;
use App\Security\Domain\Model\SignatureRequest\DocumentSigned;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\DomainEventSubscriber;
use App\Shared\Domain\Event\RegisterTrait;

final class ConfirmTrainingAttendanceOnDocumentSignedSubscriber implements DomainEventSubscriber
{
    use RegisterTrait;

    public function __construct(private readonly TrainingAttendanceRepository $trainingAttendanceRepository)
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

        $signedFilePath = $event->documentSigned()->path() ?? '';

        $attendances = $this->trainingAttendanceRepository->findBySignatureRequestId($event->signatureRequestId());

        foreach ($attendances as $attendance) {
            $attendance->endSignatureProcess($signedFilePath);
            $this->trainingAttendanceRepository->add($attendance);
        }
    }
}
