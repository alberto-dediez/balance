<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class TransportActivityRemoved extends DomainEvent
{
    protected static string $eventName = 'transportActivityRemoved';

    public function __construct(
        private ActivityId $activityId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function activityId(): ActivityId
    {
        return $this->activityId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->activityId;
    }

    public function payload(): array
    {
        return [
            'activityId' => (string) $this->activityId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            activityId: new ActivityId($payload['activityId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
