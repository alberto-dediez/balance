<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Reason;

use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\Action\Priority;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ReasonUpdated extends DomainEvent
{
    protected static string $eventName = 'reasonUpdated';

    public function __construct(
        private ReasonId $reasonId,
        private string $description,
        private Priority $priority,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function reasonId(): ReasonId
    {
        return $this->reasonId;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function priority(): Priority
    {
        return $this->priority;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->reasonId();
    }

    public function payload(): array
    {
        return [
            'reasonId' => (string) $this->reasonId,
            'description' => $this->description,
            'priority' => $this->priority->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $priority = Priority::tryFrom($payload['priority']);
        assert(null !== $priority);

        return new ReasonUpdated(
            reasonId: new ReasonId($payload['reasonId']),
            description: $payload['description'],
            priority: $priority,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
