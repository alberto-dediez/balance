<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Transport;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class TransportEmissionFactorRemoved extends DomainEvent
{
    protected static string $eventName = 'transportEmissionFactorRemoved';

    public function __construct(
        private TransportId $transportId,
        private int $year,
        private EmissionFactorGroupId $emissionFactorGroupId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function transportId(): TransportId
    {
        return $this->transportId;
    }

    public function year(): int
    {
        return $this->year;
    }

    public function emissionFactorGroupId(): EmissionFactorGroupId
    {
        return $this->emissionFactorGroupId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->transportId();
    }

    public function payload(): array
    {
        return [
            'transportId' => (string) $this->transportId,
            'year' => $this->year,
            'emissionFactorGroupId' => (string) $this->emissionFactorGroupId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new TransportEmissionFactorRemoved(
            transportId: new TransportId($payload['transportId'] ?? $payload['roadTransportId']),
            year: $payload['year'],
            emissionFactorGroupId: new EmissionFactorGroupId($payload['emissionFactorGroupId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
