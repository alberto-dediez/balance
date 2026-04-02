<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\Action\Priority;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CriticalPointActionLinked extends DomainEvent
{
    protected static string $eventName = 'criticalPointActionLinked';

    public function __construct(
        private CriticalPointId $criticalPointId,
        private ActionId $actionId,
        private Priority $priority,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function criticalPointId(): CriticalPointId
    {
        return $this->criticalPointId;
    }

    public function actionId(): ActionId
    {
        return $this->actionId;
    }

    public function priority(): Priority
    {
        return $this->priority;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->criticalPointId();
    }

    public function payload(): array
    {
        return [
            'criticalPointId' => (string) $this->criticalPointId,
            'actionId' => (string) $this->actionId,
            'priority' => $this->priority->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CriticalPointActionLinked(
            criticalPointId: new CriticalPointId($payload['criticalPointId']),
            actionId: new ActionId($payload['actionId']),
            priority: Priority::from($payload['priority']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
