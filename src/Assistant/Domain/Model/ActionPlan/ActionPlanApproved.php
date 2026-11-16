<?php

declare(strict_types=1);

namespace App\Assistant\Domain\Model\ActionPlan;

use App\Assistant\Domain\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ActionPlanApproved extends DomainEvent
{
    protected static string $eventName = 'actionPlanApproved';

    public function __construct(
        private readonly ActionPlanId $planId,
        private readonly UserId $approvedBy,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function planId(): ActionPlanId
    {
        return $this->planId;
    }

    public function approvedBy(): UserId
    {
        return $this->approvedBy;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->planId;
    }

    public function payload(): array
    {
        return [
            'planId' => (string) $this->planId,
            'approvedBy' => (string) $this->approvedBy,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ActionPlanApproved(
            planId: new ActionPlanId((string) $payload['planId']),
            approvedBy: new UserId((string) $payload['approvedBy']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
