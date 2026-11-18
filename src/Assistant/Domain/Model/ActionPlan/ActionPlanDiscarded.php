<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\ActionPlan;

use App\Assistant\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ActionPlanDiscarded extends DomainEvent
{
    protected static string $eventName = 'actionPlanDiscarded';

    public function __construct(
        private readonly ActionPlanId $planId,
        private readonly DiscardReason $reason,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function planId(): ActionPlanId
    {
        return $this->planId;
    }

    public function reason(): DiscardReason
    {
        return $this->reason;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->planId;
    }

    public function payload(): array
    {
        return [
            'planId' => (string) $this->planId,
            'reason' => $this->reason->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ActionPlanDiscarded(
            planId: new ActionPlanId((string) $payload['planId']),
            reason: DiscardReason::from((string) $payload['reason']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
