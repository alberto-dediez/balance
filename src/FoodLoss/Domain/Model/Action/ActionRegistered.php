<?php

namespace App\FoodLoss\Domain\Model\Action;

use DateTimeImmutable;
use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ActionRegistered extends DomainEvent
{
    protected static string $eventName = 'actionRegistered';

    public function __construct(
        private ActionId $actionId, 
        private string $description,
        private Priority $priority = Priority::Prevention,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
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

    public function aggregateRootId(): ?AggregateRootId
    {
        return $this->actionId();
    }

    public function payload(): ?array
    {
        return [
            'actionId' => (string) $this->actionId,
            'description' => $this->description,
            'prioritiy' => $this->priority->value
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new ActionRegistered(
            actionId: new ActionId($payload['actionId']), 
            description: $payload['description'],
            priority: Priority::tryFrom($payload['priority']),
            id: $id,
            occurredOn: $occurredOn
        );
    }
}