<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EventCarbonFootprintSourceIncluded extends DomainEvent
{
    protected static string $eventName = 'eventCarbonFootprintSourceIncluded';

    public function __construct(
        private EventCarbonFootprintId $eventCarbonFootprintId,
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
            phase: $phase,
            source: $source,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
