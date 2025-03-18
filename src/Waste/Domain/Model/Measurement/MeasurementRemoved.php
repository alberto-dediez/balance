<?php

namespace App\Waste\Domain\Model\Measurement;

use DateTimeImmutable;
use App\Waste\Domain\DomainEvent;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Shared\Domain\Event\DomainEventId;

final class MeasurementRemoved extends DomainEvent
{
    protected static string $eventName = 'measurementRemoved';

    public function __construct(
        private MeasurementId $measurementId, 
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function measurementId(): MeasurementId
    {
        return $this->measurementId;
    }

    public function payload(): ?array
    {
        return [
            'measurementId' => (string) $this->measurementId
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new MeasurementRemoved(
            measurementId: new MeasurementId($payload['measurementId']),
            id: $id,
            occurredOn: $occurredOn
        );
    }
}