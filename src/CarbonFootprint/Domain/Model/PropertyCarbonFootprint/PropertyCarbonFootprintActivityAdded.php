<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PropertyCarbonFootprintActivityAdded extends DomainEvent
{
    protected static string $eventName = 'propertyCarbonFootprintActivityAdded';

    public function __construct(
        private PropertyCarbonFootprintId $propertyCarbonFootprintId,
        private ActivityId $activityId,
        private Scope $scope,
        private Source $source,
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

    public function scope(): Scope
    {
        return $this->scope;
    }

    public function source(): Source
    {
        return $this->source;
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
            'scope' => $this->scope->value,
            'source' => $this->source->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $scope = Scope::tryFrom($payload['scope']);
        assert(null !== $scope);

        $source = Source::tryFrom($payload['source']);
        assert(null !== $source);

        return new self(
            propertyCarbonFootprintId: new PropertyCarbonFootprintId($payload['propertyCarbonFootprintId']),
            activityId: new ActivityId($payload['activityId']),
            scope: $scope,
            source: $source,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
