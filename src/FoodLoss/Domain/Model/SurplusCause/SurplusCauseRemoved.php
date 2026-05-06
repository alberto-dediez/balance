<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\SurplusCause;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class SurplusCauseRemoved extends DomainEvent
{
    protected static string $eventName = 'surplusCauseRemoved';

    public function __construct(
        private readonly SurplusCauseId $surplusCauseId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function surplusCauseId(): SurplusCauseId
    {
        return $this->surplusCauseId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->surplusCauseId;
    }

    public function payload(): array
    {
        return [
            'surplusCauseId' => (string) $this->surplusCauseId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new SurplusCauseRemoved(
            surplusCauseId: new SurplusCauseId($payload['surplusCauseId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
