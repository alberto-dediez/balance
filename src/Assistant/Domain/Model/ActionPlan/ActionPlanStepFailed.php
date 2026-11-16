<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\ActionPlan;

use App\Assistant\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ActionPlanStepFailed extends DomainEvent
{
    protected static string $eventName = 'actionPlanStepFailed';

    public function __construct(
        private readonly ActionPlanId $planId,
        private readonly int $position,
        private readonly string $tool,
        private readonly string $error,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function planId(): ActionPlanId
    {
        return $this->planId;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function tool(): string
    {
        return $this->tool;
    }

    public function error(): string
    {
        return $this->error;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->planId;
    }

    public function payload(): array
    {
        return [
            'planId' => (string) $this->planId,
            'position' => $this->position,
            'tool' => $this->tool,
            'error' => $this->error,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ActionPlanStepFailed(
            planId: new ActionPlanId((string) $payload['planId']),
            position: (int) $payload['position'],
            tool: (string) $payload['tool'],
            error: (string) $payload['error'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
