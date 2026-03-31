<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Action;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ActionUpdated extends DomainEvent
{
    protected static string $eventName = 'actionUpdated';

    public function __construct(
        private ActionId $actionId,
        private string $description,
        private Priority $priority = Priority::Prevention,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function actionId(): ActionId
    {
        return $this->actionId;
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
        return $this->actionId();
    }

    public function payload(): array
    {
        return [
            'actionId' => (string) $this->actionId,
            'description' => $this->description,
            'priority' => $this->priority,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $priority = Priority::tryFrom($payload['priority']);
        assert(null !== $priority);

        return new ActionUpdated(
            actionId: new ActionId($payload['actionId']),
            description: $payload['description'],
            priority: $priority,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
