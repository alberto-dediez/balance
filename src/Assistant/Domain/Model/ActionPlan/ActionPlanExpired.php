<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\ActionPlan;

use App\Assistant\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ActionPlanExpired extends DomainEvent
{
    protected static string $eventName = 'actionPlanExpired';

    public function __construct(
        private readonly ActionPlanId $planId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function planId(): ActionPlanId
    {
        return $this->planId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->planId;
    }

    public function payload(): array
    {
        return [
            'planId' => (string) $this->planId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ActionPlanExpired(
            planId: new ActionPlanId((string) $payload['planId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
