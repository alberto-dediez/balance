<?php

declare(strict_types=1);

namespace App\Waste\Domain\Model\Alert;

use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Waste\Domain\DomainEvent;

final class AlertAcknowledged extends DomainEvent
{
    protected static string $eventName = 'alertAcknowledged';

    public function __construct(
        private readonly AlertId $alertId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function alertId(): AlertId
    {
        return $this->alertId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->alertId;
    }

    public function payload(): array
    {
        return [
            'alertId' => (string) $this->alertId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new AlertAcknowledged(
            alertId: new AlertId($payload['alertId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
