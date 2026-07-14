<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EventCarbonFootprintReportRegistered extends DomainEvent
{
    protected static string $eventName = 'eventCarbonFootprintReportRegistered';

    public function __construct(
        private readonly EventCarbonFootprintReportId $eventCarbonFootprintReportId,
        private readonly EventCarbonFootprintId $eventCarbonFootprintId,
        private readonly AccountId $accountId,
        private readonly UserId $requestedBy,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function eventCarbonFootprintReportId(): EventCarbonFootprintReportId
    {
        return $this->eventCarbonFootprintReportId;
    }

    public function eventCarbonFootprintId(): EventCarbonFootprintId
    {
        return $this->eventCarbonFootprintId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function requestedBy(): UserId
    {
        return $this->requestedBy;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->eventCarbonFootprintReportId;
    }

    public function payload(): array
    {
        return [
            'eventCarbonFootprintReportId' => (string) $this->eventCarbonFootprintReportId,
            'eventCarbonFootprintId' => (string) $this->eventCarbonFootprintId,
            'accountId' => (string) $this->accountId,
            'requestedBy' => (string) $this->requestedBy,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            eventCarbonFootprintReportId: new EventCarbonFootprintReportId($payload['eventCarbonFootprintReportId']),
            eventCarbonFootprintId: new EventCarbonFootprintId($payload['eventCarbonFootprintId']),
            accountId: new AccountId($payload['accountId']),
            requestedBy: new UserId($payload['requestedBy']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
