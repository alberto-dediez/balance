<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EventCarbonFootprintReportFailed extends DomainEvent
{
    protected static string $eventName = 'eventCarbonFootprintReportFailed';

    public function __construct(
        private readonly EventCarbonFootprintReportId $eventCarbonFootprintReportId,
        private readonly string $errorMessage,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function eventCarbonFootprintReportId(): EventCarbonFootprintReportId
    {
        return $this->eventCarbonFootprintReportId;
    }

    public function errorMessage(): string
    {
        return $this->errorMessage;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->eventCarbonFootprintReportId;
    }

    public function payload(): array
    {
        return [
            'eventCarbonFootprintReportId' => (string) $this->eventCarbonFootprintReportId,
            'errorMessage' => $this->errorMessage,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            eventCarbonFootprintReportId: new EventCarbonFootprintReportId($payload['eventCarbonFootprintReportId']),
            errorMessage: $payload['errorMessage'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
