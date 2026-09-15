<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\RefrigerantGas;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class RefrigerantGasRemoved extends DomainEvent
{
    protected static string $eventName = 'refrigerantGasRemoved';

    public function __construct(
        private RefrigerantGasId $refrigerantGasId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function refrigerantGasId(): RefrigerantGasId
    {
        return $this->refrigerantGasId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->refrigerantGasId();
    }

    public function payload(): array
    {
        return [
            'refrigerantGasId' => (string) $this->refrigerantGasId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new RefrigerantGasRemoved(
            refrigerantGasId: new RefrigerantGasId($payload['refrigerantGasId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
