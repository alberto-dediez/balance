<?php

namespace App\Waste\Domain\Model\Measurement;

use DateTimeImmutable;
use App\Waste\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Waste\Domain\Model\Measurement\MeasurementId;

final class MeasurementUpdated extends DomainEvent
{
    protected static string $eventName = 'measurementUpdated';

    public function __construct(
        private readonly MeasurementId $measurementId,
        private readonly int $quantity = 0,
        private readonly float $load = 0,
        private readonly ?string $justification = null,
        private readonly ?string $reference = null,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function measurementId(): MeasurementId
    {
        return $this->measurementId;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function load(): float
    {
        return $this->load;
    }

    public function justification(): ?string
    {
        return $this->justification;
    }

    public function reference(): ?string
    {
        return $this->reference;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->measurementId;
    }

    public function payload(): array
    {
        return [
            'measurementId' => (string) $this->measurementId,
            'quantity' => $this->quantity,
            'load' => $this->load,
            'justification' => $this->justification,
            '$this->reference' => $this->reference
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        array $payload = []
    ): static {
        return new MeasurementUpdated(
            measurementId: new MeasurementId($payload['measurementId']),
            quantity: $payload['quantity'],
            load: $payload['load'],
            justification: $payload['justification'],
            reference: $payload['reference'] ?? null,
            id: $id,
            occurredOn: $occurredOn
        );
    }
}
