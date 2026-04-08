<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ProposedActionRemoved extends DomainEvent
{
    protected static string $eventName = 'proposedActionRemoved';

    public function __construct(
        private readonly PreventionPlanId $preventionPlanId,
        private readonly int $versionNumber,
        private readonly PropertyId $propertyId,
        private readonly ActionId $actionId,
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

    public function propertyId(): PropertyId
    {
        return $this->propertyId;
    }

    public function actionId(): ActionId
    {
        return $this->actionId;
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
            'propertyId' => (string) $this->propertyId,
            'actionId' => (string) $this->actionId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ProposedActionRemoved(
            preventionPlanId: new PreventionPlanId($payload['preventionPlanId']),
            versionNumber: (int) $payload['versionNumber'],
            propertyId: new PropertyId($payload['propertyId']),
            actionId: new ActionId($payload['actionId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
