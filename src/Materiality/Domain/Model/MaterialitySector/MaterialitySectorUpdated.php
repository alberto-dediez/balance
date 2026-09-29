<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialitySector;

use App\Materiality\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class MaterialitySectorUpdated extends DomainEvent
{
    protected static string $eventName = 'materialitySectorUpdated';

    public function __construct(
        private MaterialitySectorId $materialitySectorId,
        private string $name,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function materialitySectorId(): MaterialitySectorId
    {
        return $this->materialitySectorId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->materialitySectorId();
    }

    public function payload(): array
    {
        return [
            'materialitySectorId' => (string) $this->materialitySectorId,
            'name' => $this->name,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new MaterialitySectorUpdated(
            materialitySectorId: new MaterialitySectorId($payload['materialitySectorId']),
            name: $payload['name'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
