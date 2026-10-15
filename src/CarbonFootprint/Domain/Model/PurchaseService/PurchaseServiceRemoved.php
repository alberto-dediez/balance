<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PurchaseService;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PurchaseServiceRemoved extends DomainEvent
{
    protected static string $eventName = 'purchaseServiceRemoved';

    public function __construct(
        private PurchaseServiceId $purchaseServiceId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function purchaseServiceId(): PurchaseServiceId
    {
        return $this->purchaseServiceId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->purchaseServiceId();
    }

    public function payload(): array
    {
        return [
            'purchaseServiceId' => (string) $this->purchaseServiceId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new PurchaseServiceRemoved(
            purchaseServiceId: new PurchaseServiceId($payload['purchaseServiceId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
