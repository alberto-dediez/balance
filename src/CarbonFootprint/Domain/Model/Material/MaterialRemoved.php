<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Material;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MaterialRemoved extends DomainEvent
{
    protected static string $eventName = 'materialRemoved';

    public function __construct(
        private MaterialId $materialId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function materialId(): MaterialId
    {
        return $this->materialId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->materialId();
    }

    public function payload(): array
    {
        return [
            'materialId' => (string) $this->materialId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MaterialRemoved(
            materialId: new MaterialId($payload['materialId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
