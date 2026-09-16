<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PropertyCarbonFootprintActivityRemoved extends DomainEvent
{
    protected static string $eventName = 'propertyCarbonFootprintActivityRemoved';

    public function __construct(
        private PropertyCarbonFootprintId $propertyCarbonFootprintId,
        private ActivityId $activityId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function propertyCarbonFootprintId(): PropertyCarbonFootprintId
    {
        return $this->propertyCarbonFootprintId;
    }

    public function activityId(): ActivityId
    {
        return $this->activityId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->propertyCarbonFootprintId;
    }

    public function payload(): array
    {
        return [
            'propertyCarbonFootprintId' => (string) $this->propertyCarbonFootprintId,
            'activityId' => (string) $this->activityId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            propertyCarbonFootprintId: new PropertyCarbonFootprintId($payload['propertyCarbonFootprintId']),
            activityId: new ActivityId($payload['activityId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
