<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\FoodLoss\Domain\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PreventionPlanVersionCreated extends DomainEvent
{
    protected static string $eventName = 'preventionPlanVersionCreated';

    public function __construct(
        private PreventionPlanId $preventionPlanId,
        private int $versionNumber,
        private UserId $createdBy,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function preventionPlanId(): PreventionPlanId
    {
        return $this->preventionPlanId;
    }

    public function versionNumber(): int
    {
        return $this->versionNumber;
    }

    public function createdBy(): UserId
    {
        return $this->createdBy;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->preventionPlanId();
    }

    public function payload(): array
    {
        return [
            'preventionPlanId' => (string) $this->preventionPlanId,
            'versionNumber' => $this->versionNumber,
            'createdBy' => (string) $this->createdBy,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new PreventionPlanVersionCreated(
            preventionPlanId: new PreventionPlanId($payload['preventionPlanId']),
            versionNumber: (int) $payload['versionNumber'],
            createdBy: new UserId($payload['createdBy']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
