<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class CompanyCarbonFootprintActivityRemoved extends DomainEvent
{
    protected static string $eventName = 'companyCarbonFootprintActivityRemoved';

    public function __construct(
        private CompanyCarbonFootprintId $companyCarbonFootprintId,
        private ActivityId $activityId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function companyCarbonFootprintId(): CompanyCarbonFootprintId
    {
        return $this->companyCarbonFootprintId;
    }

    public function activityId(): ActivityId
    {
        return $this->activityId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->companyCarbonFootprintId;
    }

    public function payload(): array
    {
        return [
            'companyCarbonFootprintId' => (string) $this->companyCarbonFootprintId,
            'activityId' => (string) $this->activityId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            companyCarbonFootprintId: new CompanyCarbonFootprintId($payload['companyCarbonFootprintId']),
            activityId: new ActivityId($payload['activityId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
