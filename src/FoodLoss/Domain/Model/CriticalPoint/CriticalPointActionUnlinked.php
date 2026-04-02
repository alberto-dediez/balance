<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\CriticalPoint;

use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CriticalPointActionUnlinked extends DomainEvent
{
    protected static string $eventName = 'criticalPointActionUnlinked';

    public function __construct(
        private CriticalPointId $criticalPointId,
        private ActionId $actionId,
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

    public function aggregateRootId(): AggregateRootId
    {
        return $this->criticalPointId();
    }

    public function payload(): array
    {
        return [
            'criticalPointId' => (string) $this->criticalPointId,
            'actionId' => (string) $this->actionId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new CriticalPointActionUnlinked(
            criticalPointId: new CriticalPointId($payload['criticalPointId']),
            actionId: new ActionId($payload['actionId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
