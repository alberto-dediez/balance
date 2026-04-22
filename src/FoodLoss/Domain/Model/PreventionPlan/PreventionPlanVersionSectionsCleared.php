<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\FoodLoss\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PreventionPlanVersionSectionsCleared extends DomainEvent
{
    protected static string $eventName = 'preventionPlanVersionSectionsCleared';

    public function __construct(
        private readonly PreventionPlanId $preventionPlanId,
        private readonly int $versionNumber,
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

    public function aggregateRootId(): AggregateRootId
    {
        return $this->preventionPlanId;
    }

    public function payload(): array
    {
        return [
            'preventionPlanId' => (string) $this->preventionPlanId,
            'versionNumber' => $this->versionNumber,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new PreventionPlanVersionSectionsCleared(
            preventionPlanId: new PreventionPlanId($payload['preventionPlanId']),
            versionNumber: (int) $payload['versionNumber'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
