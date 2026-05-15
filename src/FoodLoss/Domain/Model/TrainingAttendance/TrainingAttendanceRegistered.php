<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\TrainingAttendance;

use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\TrainingSession\TrainingSessionId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class TrainingAttendanceRegistered extends DomainEvent
{
    protected static string $eventName = 'trainingAttendanceRegistered';

    public function __construct(
        private TrainingAttendanceId $trainingAttendanceId,
        private TrainingSessionId $trainingSessionId,
        private EmployeeId $employeeId,
        private AccountId $accountId,
        private \DateTimeImmutable $attendedAt,
        private string $filePath,
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

    public function filePath(): string
    {
        return $this->filePath;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->trainingAttendanceId();
    }

    public function payload(): array
    {
        return [
            'trainingAttendanceId' => (string) $this->trainingAttendanceId,
            'trainingSessionId' => (string) $this->trainingSessionId,
            'employeeId' => (string) $this->employeeId,
            'accountId' => (string) $this->accountId,
            'attendedAt' => $this->attendedAt->format(\DateTimeInterface::ATOM),
            'filePath' => $this->filePath,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new TrainingAttendanceRegistered(
            trainingAttendanceId: new TrainingAttendanceId($payload['trainingAttendanceId']),
            trainingSessionId: new TrainingSessionId($payload['trainingSessionId']),
            employeeId: new EmployeeId($payload['employeeId']),
            accountId: new AccountId($payload['accountId']),
            attendedAt: new \DateTimeImmutable($payload['attendedAt']),
            filePath: $payload['filePath'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
