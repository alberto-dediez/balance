<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\TrainingAttendance;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class TrainingAttendanceSignatureCompleted extends DomainEvent
{
    protected static string $eventName = 'trainingAttendanceSignatureCompleted';

    public function __construct(
        private readonly TrainingAttendanceId $trainingAttendanceId,
        private readonly string $signedFilePath,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function trainingAttendanceId(): TrainingAttendanceId
    {
        return $this->trainingAttendanceId;
    }

    public function signedFilePath(): string
    {
        return $this->signedFilePath;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->trainingAttendanceId;
    }

    public function payload(): array
    {
        return [
            'trainingAttendanceId' => (string) $this->trainingAttendanceId,
            'signedFilePath' => $this->signedFilePath,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new TrainingAttendanceSignatureCompleted(
            trainingAttendanceId: new TrainingAttendanceId($payload['trainingAttendanceId']),
            signedFilePath: $payload['signedFilePath'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
