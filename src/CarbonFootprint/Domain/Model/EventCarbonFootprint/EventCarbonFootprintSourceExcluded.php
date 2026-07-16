<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EventCarbonFootprintSourceExcluded extends DomainEvent
{
    protected static string $eventName = 'eventCarbonFootprintSourceExcluded';

    public function __construct(
        private EventCarbonFootprintId $eventCarbonFootprintId,
        private Phase $phase,
        private Source $source,
        private ExclusionReason $reason,
        private ?string $comment = null,
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

    public function reason(): ExclusionReason
    {
        return $this->reason;
    }

    public function comment(): ?string
    {
        return $this->comment;
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
            'reason' => $this->reason->value,
            'comment' => $this->comment,
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

        $reason = ExclusionReason::tryFrom($payload['reason']);
        assert(null !== $reason);

        return new self(
            eventCarbonFootprintId: new EventCarbonFootprintId($payload['eventCarbonFootprintId']),
            phase: $phase,
            source: $source,
            reason: $reason,
            comment: $payload['comment'] ?? null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
