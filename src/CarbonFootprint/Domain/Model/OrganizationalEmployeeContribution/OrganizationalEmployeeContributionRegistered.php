<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalEmployeeContribution;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class OrganizationalEmployeeContributionRegistered extends DomainEvent
{
    protected static string $eventName = 'organizationalEmployeeContributionRegistered';

    public function __construct(
        private OrganizationalEmployeeContributionId $contributionId,
        private CarbonFootprintReportSubjectType $subjectType,
        private string $carbonFootprintId,
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

    public function contributionId(): OrganizationalEmployeeContributionId
    {
        return $this->contributionId;
    }

    public function subjectType(): CarbonFootprintReportSubjectType
    {
        return $this->subjectType;
    }

    public function carbonFootprintId(): string
    {
        return $this->carbonFootprintId;
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
        return $this->contributionId;
    }

    public function payload(): array
    {
        return [
            'contributionId' => (string) $this->contributionId,
            'subjectType' => $this->subjectType->value,
            'carbonFootprintId' => $this->carbonFootprintId,
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
            contributionId: new OrganizationalEmployeeContributionId($payload['contributionId']),
            subjectType: CarbonFootprintReportSubjectType::from($payload['subjectType']),
            carbonFootprintId: $payload['carbonFootprintId'],
            accountId: new AccountId($payload['accountId']),
            employeeId: new EmployeeId($payload['employeeId']),
            token: $payload['token'],
            employeeName: $payload['employeeName'] ?? null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
