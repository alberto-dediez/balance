<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\TrainingAttendance;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class TrainingAttendanceSignatureCancelled extends DomainEvent
{
    protected static string $eventName = 'trainingAttendanceSignatureCancelled';

    public function __construct(
        private readonly TrainingAttendanceId $trainingAttendanceId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function trainingAttendanceId(): TrainingAttendanceId
    {
        return $this->trainingAttendanceId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->trainingAttendanceId;
    }

    public function payload(): array
    {
        return [
            'trainingAttendanceId' => (string) $this->trainingAttendanceId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new TrainingAttendanceSignatureCancelled(
            trainingAttendanceId: new TrainingAttendanceId($payload['trainingAttendanceId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
