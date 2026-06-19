<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\DeliveryLog;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class DeliveryLogSignatureCancelled extends DomainEvent
{
    protected static string $eventName = 'deliveryLogSignatureCancelled';

    public function __construct(
        private readonly DeliveryLogId $deliveryLogId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function deliveryLogId(): DeliveryLogId
    {
        return $this->deliveryLogId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->deliveryLogId;
    }

    public function payload(): array
    {
        return [
            'deliveryLogId' => (string) $this->deliveryLogId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new DeliveryLogSignatureCancelled(
            deliveryLogId: new DeliveryLogId($payload['deliveryLogId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
