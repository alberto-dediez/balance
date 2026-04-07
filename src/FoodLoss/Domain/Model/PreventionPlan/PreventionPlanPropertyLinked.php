<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\PreventionPlan;

use App\FoodLoss\Domain\DomainEvent;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PreventionPlanPropertyLinked extends DomainEvent
{
    protected static string $eventName = 'preventionPlanPropertyLinked';

    public function __construct(
        private readonly PreventionPlanId $preventionPlanId,
        private readonly PropertyId $propertyId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function preventionPlanId(): PreventionPlanId
    {
        return $this->preventionPlanId;
    }

    public function propertyId(): PropertyId
    {
        return $this->propertyId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->preventionPlanId;
    }

    public function payload(): array
    {
        return [
            'preventionPlanId' => (string) $this->preventionPlanId,
            'propertyId' => (string) $this->propertyId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new PreventionPlanPropertyLinked(
            preventionPlanId: new PreventionPlanId($payload['preventionPlanId']),
            propertyId: new PropertyId($payload['propertyId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
