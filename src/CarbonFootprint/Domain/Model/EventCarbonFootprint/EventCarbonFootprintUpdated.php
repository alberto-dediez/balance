<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprint;

use App\CarbonFootprint\Domain\DomainEvent;
use App\EventOperations\Domain\Model\Event\DateRange;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EventCarbonFootprintUpdated extends DomainEvent
{
    protected static string $eventName = 'eventCarbonFootprintUpdated';

    public function __construct(
        private EventCarbonFootprintId $eventCarbonFootprintId,
        private DateRange $calculationPeriod,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function eventCarbonFootprintId(): EventCarbonFootprintId
    {
        return $this->eventCarbonFootprintId;
    }

    public function calculationPeriod(): DateRange
    {
        return $this->calculationPeriod;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->eventCarbonFootprintId();
    }

    public function payload(): array
    {
        return [
            'eventCarbonFootprintId' => (string) $this->eventCarbonFootprintId,
            'calculationPeriodStartDate' => $this->calculationPeriod->startDate()?->format(\DateTimeInterface::ATOM),
            'calculationPeriodEndDate' => $this->calculationPeriod->endDate()?->format(\DateTimeInterface::ATOM),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        $status = EventCarbonFootprintStatus::tryFrom($payload['status']);
        assert(null !== $status);

        return new EventCarbonFootprintUpdated(
            eventCarbonFootprintId: new EventCarbonFootprintId($payload['eventCarbonFootprintId']),
            calculationPeriod: new DateRange(
                startDate: isset($payload['calculationPeriodStartDate']) ? new \DateTimeImmutable($payload['calculationPeriodStartDate']) : null,
                endDate: isset($payload['calculationPeriodEndDate']) ? new \DateTimeImmutable($payload['calculationPeriodEndDate']) : null,
            ),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
