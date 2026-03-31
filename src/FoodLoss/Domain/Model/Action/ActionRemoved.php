<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Action;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ActionRemoved extends DomainEvent
{
    protected static string $eventName = 'actionRemoved';

    public function __construct(
        private ActionId $actionId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function actionId(): ActionId
    {
        return $this->actionId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->actionId();
    }

    public function payload(): array
    {
        return [
            'actionId' => (string) $this->actionId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ActionRemoved(
            actionId: new ActionId($payload['actionId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
