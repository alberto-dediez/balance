<?php

declare(strict_types=1);

namespace App\Property\Domain\Model\Destination;

use App\Property\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class DestinationRemoved extends DomainEvent
{
    protected static string $eventName = 'destinationRemoved';

    public function __construct(
        private readonly DestinationId $destinationId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function destinationId(): DestinationId
    {
        return $this->destinationId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->destinationId;
    }

    public function payload(): array
    {
        return [
            'destinationId' => (string) $this->destinationId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new DestinationRemoved(
            destinationId: new DestinationId($payload['destinationId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
