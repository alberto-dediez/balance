<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Surplus;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class SurplusEliminationAssessed extends DomainEvent
{
    protected static string $eventName = 'surplusEliminationAssessed';

    public function __construct(
        private readonly SurplusId $surplusId,
        private readonly bool $canBeEliminated,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function surplusId(): SurplusId
    {
        return $this->surplusId;
    }

    public function canBeEliminated(): bool
    {
        return $this->canBeEliminated;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->surplusId;
    }

    public function payload(): array
    {
        return [
            'surplusId' => (string) $this->surplusId,
            'canBeEliminated' => $this->canBeEliminated,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new SurplusEliminationAssessed(
            surplusId: new SurplusId($payload['surplusId']),
            canBeEliminated: (bool) $payload['canBeEliminated'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
