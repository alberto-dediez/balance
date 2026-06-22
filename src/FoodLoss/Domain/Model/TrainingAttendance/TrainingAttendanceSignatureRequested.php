<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\TrainingAttendance;

use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\SignatureRequest\SignatureRequestId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class TrainingAttendanceSignatureRequested extends DomainEvent
{
    protected static string $eventName = 'trainingAttendanceSignatureRequested';

    public function __construct(
        private readonly TrainingAttendanceId $trainingAttendanceId,
        private readonly TrainingSessionId $trainingSessionId,
        private readonly EmployeeId $employeeId,
        private readonly AccountId $accountId,
        private readonly \DateTimeImmutable $attendedAt,
        private readonly SignatureRequestId $signatureRequestId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function trainingAttendanceId(): TrainingAttendanceId
    {
        return $this->trainingAttendanceId;
    }

    public function trainingSessionId(): TrainingSessionId
    {
        return $this->trainingSessionId;
    }

    public function employeeId(): EmployeeId
    {
        return $this->employeeId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function attendedAt(): \DateTimeImmutable
    {
        return $this->attendedAt;
    }

    public function signatureRequestId(): SignatureRequestId
    {
        return $this->signatureRequestId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->trainingAttendanceId;
    }

    public function payload(): array
    {
        return [
            'trainingAttendanceId' => (string) $this->trainingAttendanceId,
            'trainingSessionId' => (string) $this->trainingSessionId,
            'employeeId' => (string) $this->employeeId,
            'accountId' => (string) $this->accountId,
            'attendedAt' => $this->attendedAt->format(\DateTimeInterface::ATOM),
            'signatureRequestId' => (string) $this->signatureRequestId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new TrainingAttendanceSignatureRequested(
            trainingAttendanceId: new TrainingAttendanceId($payload['trainingAttendanceId']),
            trainingSessionId: new TrainingSessionId($payload['trainingSessionId']),
            employeeId: new EmployeeId($payload['employeeId']),
            accountId: new AccountId($payload['accountId']),
            attendedAt: new \DateTimeImmutable($payload['attendedAt']),
            signatureRequestId: new SignatureRequestId($payload['signatureRequestId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
