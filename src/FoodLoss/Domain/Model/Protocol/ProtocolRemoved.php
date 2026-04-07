<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Protocol;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ProtocolRemoved extends DomainEvent
{
    protected static string $eventName = 'protocolRemoved';

    public function __construct(
        private ProtocolId $protocolId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function protocolId(): ProtocolId
    {
        return $this->protocolId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->protocolId();
    }

    public function payload(): array
    {
        return [
            'protocolId' => (string) $this->protocolId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ProtocolRemoved(
            protocolId: new ProtocolId($payload['protocolId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
