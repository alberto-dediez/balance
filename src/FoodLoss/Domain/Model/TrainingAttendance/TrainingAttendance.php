<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\TrainingAttendance;

use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class TrainingAttendance implements \Stringable
{
    use AggregateRoot;

    /** @param array<DomainEvent> $events */
    public function __construct(
        private TrainingAttendanceId $id,
        private ?TrainingSessionId $trainingSessionId = null,
        private ?EmployeeId $employeeId = null,
        private ?AccountId $accountId = null,
        private \DateTimeImmutable $attendedAt = new \DateTimeImmutable('NOW'),
        private string $filePath = '',
        private bool $removed = false,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $removedAt = null,
        private ?SignatureRequestId $signatureRequestId = null,
        private ?\DateTimeImmutable $updatedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): TrainingAttendanceId
    {
        return $this->id;
    }

    public function trainingSessionId(): ?TrainingSessionId
    {
        return $this->trainingSessionId;
    }

    public function employeeId(): ?EmployeeId
    {
        return $this->employeeId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function attendedAt(): \DateTimeImmutable
    {
        return $this->attendedAt;
    }

    public function filePath(): string
    {
        return $this->filePath;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function signatureRequestId(): ?SignatureRequestId
    {
        return $this->signatureRequestId;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isInSignatureProcess(): bool
    {
        return null !== $this->signatureRequestId && '' === $this->filePath;
    }

    public function register(
        TrainingSessionId $trainingSessionId,
        EmployeeId $employeeId,
        AccountId $accountId,
        \DateTimeImmutable $attendedAt,
        string $filePath,
    ): void {
        $this->recordApplyAndPublish(
            new TrainingAttendanceRegistered(
                trainingAttendanceId: $this->id(),
                trainingSessionId: $trainingSessionId,
                employeeId: $employeeId,
                accountId: $accountId,
                attendedAt: $attendedAt,
                filePath: $filePath,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new TrainingAttendanceRemoved(
                trainingAttendanceId: $this->id(),
            ),
        );
    }

    public function startSignatureProcess(
        TrainingSessionId $trainingSessionId,
        EmployeeId $employeeId,
        AccountId $accountId,
        \DateTimeImmutable $attendedAt,
        SignatureRequestId $signatureRequestId,
    ): void {
        $this->recordApplyAndPublish(
            new TrainingAttendanceSignatureRequested(
                trainingAttendanceId: $this->id(),
                trainingSessionId: $trainingSessionId,
                employeeId: $employeeId,
                accountId: $accountId,
                attendedAt: $attendedAt,
                signatureRequestId: $signatureRequestId,
            ),
        );
    }

    public function cancelSignatureProcess(): void
    {
        if (!$this->isInSignatureProcess()) {
            throw new \InvalidArgumentException('Cannot cancel signature process on not started one.');
        }

        $this->recordApplyAndPublish(
            new TrainingAttendanceSignatureCancelled(
                trainingAttendanceId: $this->id(),
            ),
        );
    }

    public function endSignatureProcess(string $signedFilePath): void
    {
        if (!$this->isInSignatureProcess()) {
            throw new \InvalidArgumentException('Cannot end signature process on not started one.');
        }

        $this->recordApplyAndPublish(
            new TrainingAttendanceSignatureCompleted(
                trainingAttendanceId: $this->id(),
                signedFilePath: $signedFilePath,
            ),
        );
    }

    public function applyTrainingAttendanceRegistered(TrainingAttendanceRegistered $event): void
    {
        $this->trainingSessionId = $event->trainingSessionId();
        $this->employeeId = $event->employeeId();
        $this->accountId = $event->accountId();
        $this->attendedAt = $event->attendedAt();
        $this->filePath = $event->filePath();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyTrainingAttendanceRemoved(TrainingAttendanceRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = $event->occurredOn();
    }

    public function applyTrainingAttendanceSignatureRequested(TrainingAttendanceSignatureRequested $event): void
    {
        $this->trainingSessionId = $event->trainingSessionId();
        $this->employeeId = $event->employeeId();
        $this->accountId = $event->accountId();
        $this->attendedAt = $event->attendedAt();
        $this->signatureRequestId = $event->signatureRequestId();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyTrainingAttendanceSignatureCancelled(TrainingAttendanceSignatureCancelled $event): void
    {
        $this->signatureRequestId = null;
        $this->updatedAt = $event->occurredOn();
    }

    public function applyTrainingAttendanceSignatureCompleted(TrainingAttendanceSignatureCompleted $event): void
    {
        $this->filePath = $event->signedFilePath();
        $this->signatureRequestId = null;
        $this->updatedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
