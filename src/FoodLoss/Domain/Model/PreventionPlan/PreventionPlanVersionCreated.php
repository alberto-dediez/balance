<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\FoodLoss\Domain\DomainEvent;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
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
        private ?EmployeeId $responsible = null,
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

    public function responsible(): ?EmployeeId
    {
        return $this->responsible;
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
            'responsible' => null !== $this->responsible ? (string) $this->responsible : null,
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
            responsible: !empty($payload['responsible']) ? new EmployeeId($payload['responsible']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
