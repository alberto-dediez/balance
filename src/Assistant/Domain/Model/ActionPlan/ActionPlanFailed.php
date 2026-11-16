<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\ActionPlan;

use App\Assistant\Domain\DomainEvent;
use App\Assistant\Domain\Model\AssistantUsage;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ActionPlanFailed extends DomainEvent
{
    protected static string $eventName = 'actionPlanFailed';

    public function __construct(
        private readonly ActionPlanId $planId,
        private readonly string $reason,
        private readonly AssistantUsage $usage,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function planId(): ActionPlanId
    {
        return $this->planId;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function usage(): AssistantUsage
    {
        return $this->usage;
    }

    public function costUsd(): string
    {
        return $this->usage->costUsd();
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->planId;
    }

    public function payload(): array
    {
        return [
            'planId' => (string) $this->planId,
            'reason' => $this->reason,
            ...$this->usage->toPayload(),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ActionPlanFailed(
            planId: new ActionPlanId((string) $payload['planId']),
            reason: (string) $payload['reason'],
            usage: AssistantUsage::fromPayload($payload),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
