<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmployeeContribution;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EmployeeContributionRegistered extends DomainEvent
{
    protected static string $eventName = 'employeeContributionRegistered';

    public function __construct(
        private EmployeeContributionId $employeeContributionId,
        private EventCarbonFootprintId $eventCarbonFootprintId,
        private AccountId $accountId,
        private EmployeeId $employeeId,
        #[\SensitiveParameter]
        private string $token,
        private ?string $employeeName,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function employeeContributionId(): EmployeeContributionId
    {
        return $this->employeeContributionId;
    }

    public function eventCarbonFootprintId(): EventCarbonFootprintId
    {
        return $this->eventCarbonFootprintId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function employeeId(): EmployeeId
    {
        return $this->employeeId;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function employeeName(): ?string
    {
        return $this->employeeName;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->employeeContributionId();
    }

    public function payload(): array
    {
        return [
            'employeeContributionId' => (string) $this->employeeContributionId,
            'eventCarbonFootprintId' => (string) $this->eventCarbonFootprintId,
            'accountId' => (string) $this->accountId,
            'employeeId' => (string) $this->employeeId,
            'token' => $this->token,
            'employeeName' => $this->employeeName,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            employeeContributionId: new EmployeeContributionId($payload['employeeContributionId']),
            eventCarbonFootprintId: new EventCarbonFootprintId($payload['eventCarbonFootprintId']),
            accountId: new AccountId($payload['accountId']),
            employeeId: new EmployeeId($payload['employeeId']),
            token: $payload['token'],
            employeeName: $payload['employeeName'] ?? null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
