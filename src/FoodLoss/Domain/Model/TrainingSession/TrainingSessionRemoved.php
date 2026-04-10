<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\TrainingSession;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class TrainingSessionRemoved extends DomainEvent
{
    protected static string $eventName = 'trainingSessionRemoved';

    public function __construct(
        private TrainingSessionId $trainingSessionId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function trainingSessionId(): TrainingSessionId
    {
        return $this->trainingSessionId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->trainingSessionId();
    }

    public function payload(): array
    {
        return [
            'trainingSessionId' => (string) $this->trainingSessionId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new TrainingSessionRemoved(
            trainingSessionId: new TrainingSessionId($payload['trainingSessionId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
