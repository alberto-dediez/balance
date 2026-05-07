<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\Action\ActionId;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ProposedActionAdded extends DomainEvent
{
    protected static string $eventName = 'proposedActionAdded';

    public function __construct(
        private readonly PreventionPlanId $preventionPlanId,
        private readonly int $versionNumber,
        private readonly ProposedActionId $proposedActionId,
        private readonly PropertyId $propertyId,
        private readonly ActionId $actionId,
        private readonly CriticalPointId $criticalPointId,
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

    public function proposedActionId(): ProposedActionId
    {
        return $this->proposedActionId;
    }

    public function propertyId(): PropertyId
    {
        return $this->propertyId;
    }

    public function actionId(): ActionId
    {
        return $this->actionId;
    }

    public function criticalPointId(): CriticalPointId
    {
        return $this->criticalPointId;
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
            'proposedActionId' => (string) $this->proposedActionId,
            'propertyId' => (string) $this->propertyId,
            'actionId' => (string) $this->actionId,
            'criticalPointId' => (string) $this->criticalPointId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ProposedActionAdded(
            preventionPlanId: new PreventionPlanId($payload['preventionPlanId']),
            versionNumber: (int) $payload['versionNumber'],
            proposedActionId: !empty($payload['proposedActionId']) ? new ProposedActionId($payload['proposedActionId']) : ProposedActionId::generate(),
            propertyId: new PropertyId($payload['propertyId']),
            actionId: new ActionId($payload['actionId']),
            criticalPointId: !empty($payload['criticalPointId']) ? new CriticalPointId($payload['criticalPointId']) : CriticalPointId::generate(),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
