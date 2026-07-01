<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EventCarbonFootprintActivityAdded extends DomainEvent
{
    protected static string $eventName = 'eventCarbonFootprintActivityAdded';

    public function __construct(
        private EventCarbonFootprintId $eventCarbonFootprintId,
        private ActivityId $activityId,
        private Phase $phase,
        private Source $source,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function eventCarbonFootprintId(): EventCarbonFootprintId
    {
        return $this->eventCarbonFootprintId;
    }

    public function activityId(): ActivityId
    {
        return $this->activityId;
    }

    public function phase(): Phase
    {
        return $this->phase;
    }

    public function source(): Source
    {
        return $this->source;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->eventCarbonFootprintId;
    }

    public function payload(): array
    {
        return [
            'eventCarbonFootprintId' => (string) $this->eventCarbonFootprintId,
            'activityId' => (string) $this->activityId,
            'phase' => $this->phase->value,
            'source' => $this->source->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $phase = Phase::tryFrom($payload['phase']);
        assert(null !== $phase);

        $source = Source::tryFrom($payload['source']);
        assert(null !== $source);

        return new self(
            eventCarbonFootprintId: new EventCarbonFootprintId($payload['eventCarbonFootprintId']),
            activityId: new ActivityId($payload['activityId']),
            phase: $phase,
            source: $source,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
