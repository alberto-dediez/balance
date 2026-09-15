<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\RefrigerantGas;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class RefrigerantGasUpdated extends DomainEvent
{
    protected static string $eventName = 'refrigerantGasUpdated';

    public function __construct(
        private RefrigerantGasId $refrigerantGasId,
        private string $name,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function refrigerantGasId(): RefrigerantGasId
    {
        return $this->refrigerantGasId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->refrigerantGasId();
    }

    public function payload(): array
    {
        return [
            'refrigerantGasId' => (string) $this->refrigerantGasId,
            'name' => $this->name,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new RefrigerantGasUpdated(
            refrigerantGasId: new RefrigerantGasId($payload['refrigerantGasId']),
            name: $payload['name'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
