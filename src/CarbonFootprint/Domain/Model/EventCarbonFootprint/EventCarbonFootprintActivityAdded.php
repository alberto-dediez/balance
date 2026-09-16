<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\EventOperations\Domain\Model\Provider\ProviderId;
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
        private ?ProviderId $providerId = null,
        private bool $fromEmployees = false,
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

    public function providerId(): ?ProviderId
    {
        return $this->providerId;
    }

    public function fromEmployees(): bool
    {
        return $this->fromEmployees;
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
            'providerId' => $this->providerId instanceof ProviderId ? (string) $this->providerId : null,
            'fromEmployees' => $this->fromEmployees,
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
            providerId: isset($payload['providerId']) ? new ProviderId($payload['providerId']) : null,
            fromEmployees: $payload['fromEmployees'] ?? false,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
