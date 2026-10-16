<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Transport;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class TransportRemoved extends DomainEvent
{
    protected static string $eventName = 'transportRemoved';

    public function __construct(
        private TransportId $transportId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function transportId(): TransportId
    {
        return $this->transportId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->transportId();
    }

    public function payload(): array
    {
        return [
            'transportId' => (string) $this->transportId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new TransportRemoved(
            transportId: new TransportId($payload['transportId'] ?? $payload['roadTransportId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
