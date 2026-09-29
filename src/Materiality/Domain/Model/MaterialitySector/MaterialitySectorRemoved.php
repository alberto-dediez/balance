<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialitySector;

use App\Materiality\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MaterialitySectorRemoved extends DomainEvent
{
    protected static string $eventName = 'materialitySectorRemoved';

    public function __construct(
        private MaterialitySectorId $materialitySectorId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function materialitySectorId(): MaterialitySectorId
    {
        return $this->materialitySectorId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->materialitySectorId();
    }

    public function payload(): array
    {
        return [
            'materialitySectorId' => (string) $this->materialitySectorId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MaterialitySectorRemoved(
            materialitySectorId: new MaterialitySectorId($payload['materialitySectorId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
