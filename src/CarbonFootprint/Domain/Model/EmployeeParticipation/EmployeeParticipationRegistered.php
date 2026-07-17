<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmployeeParticipation;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EmployeeParticipationRegistered extends DomainEvent
{
    protected static string $eventName = 'employeeParticipationRegistered';

    public function __construct(
        private EmployeeParticipationId $employeeParticipationId,
        private EventCarbonFootprintId $eventCarbonFootprintId,
        private AccountId $accountId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function employeeParticipationId(): EmployeeParticipationId
    {
        return $this->employeeParticipationId;
    }

    public function eventCarbonFootprintId(): EventCarbonFootprintId
    {
        return $this->eventCarbonFootprintId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->employeeParticipationId();
    }

    public function payload(): array
    {
        return [
            'employeeParticipationId' => (string) $this->employeeParticipationId,
            'eventCarbonFootprintId' => (string) $this->eventCarbonFootprintId,
            'accountId' => (string) $this->accountId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            employeeParticipationId: new EmployeeParticipationId($payload['employeeParticipationId']),
            eventCarbonFootprintId: new EventCarbonFootprintId($payload['eventCarbonFootprintId']),
            accountId: new AccountId($payload['accountId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
