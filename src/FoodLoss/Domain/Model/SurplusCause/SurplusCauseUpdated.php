<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\SurplusCause;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class SurplusCauseUpdated extends DomainEvent
{
    protected static string $eventName = 'surplusCauseUpdated';

    public function __construct(
        private readonly SurplusCauseId $surplusCauseId,
        private readonly string $name,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function surplusCauseId(): SurplusCauseId
    {
        return $this->surplusCauseId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->surplusCauseId;
    }

    public function payload(): array
    {
        return [
            'surplusCauseId' => (string) $this->surplusCauseId,
            'name' => $this->name,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new SurplusCauseUpdated(
            surplusCauseId: new SurplusCauseId($payload['surplusCauseId']),
            name: $payload['name'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
