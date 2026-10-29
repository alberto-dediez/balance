<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class OrganizationalEmployeeParticipation implements \Stringable
{
    use AggregateRoot;

    /** @param array<DomainEvent> $events */
    public function __construct(
        private OrganizationalEmployeeParticipationId $id,
        private CarbonFootprintReportSubjectType $subjectType = CarbonFootprintReportSubjectType::Property,
        private string $carbonFootprintId = '',
        private ?AccountId $accountId = null,
        private ?int $totalEmployees = null,
        private ?ConfidenceLevel $confidenceLevel = null,
        private ?float $marginOfError = null,
        private ?int $answeredCount = null,
        private ?\DateTimeImmutable $aggregatedAt = null,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): OrganizationalEmployeeParticipationId
    {
        return $this->id;
    }

    public function subjectType(): CarbonFootprintReportSubjectType
    {
        return $this->subjectType;
    }

    public function carbonFootprintId(): string
    {
        return $this->carbonFootprintId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function totalEmployees(): ?int
    {
        return $this->totalEmployees;
    }

    public function confidenceLevel(): ?ConfidenceLevel
    {
        return $this->confidenceLevel;
    }

    public function marginOfError(): ?float
    {
        return $this->marginOfError;
    }

    public function answeredCount(): ?int
    {
        return $this->answeredCount;
    }

    public function aggregatedAt(): ?\DateTimeImmutable
    {
        return $this->aggregatedAt;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function register(
        CarbonFootprintReportSubjectType $subjectType,
        string $carbonFootprintId,
        AccountId $accountId,
    ): void {
        $this->recordApplyAndPublish(
            new OrganizationalEmployeeParticipationRegistered(
                participationId: $this->id,
                subjectType: $subjectType,
                carbonFootprintId: $carbonFootprintId,
                accountId: $accountId,
            ),
        );
    }

    public function update(?int $totalEmployees, ?ConfidenceLevel $confidenceLevel): void
    {
        if (null !== $totalEmployees && $totalEmployees < 1) {
            throw new \InvalidArgumentException('The total of employees must be at least 1');
        }

        if ($this->totalEmployees === $totalEmployees && $this->confidenceLevel === $confidenceLevel) {
            return;
        }

        $this->recordApplyAndPublish(
            new OrganizationalEmployeeParticipationUpdated(
                participationId: $this->id,
                totalEmployees: $totalEmployees,
                confidenceLevel: $confidenceLevel,
            ),
        );
    }

    public function recordAggregation(int $answeredCount, ?float $marginOfError): void
    {
        $this->recordApplyAndPublish(
            new OrganizationalEmployeeParticipationAggregated(
                participationId: $this->id,
                answeredCount: $answeredCount,
                marginOfError: $marginOfError,
            ),
        );
    }

    public function applyOrganizationalEmployeeParticipationRegistered(OrganizationalEmployeeParticipationRegistered $event): void
    {
        $this->subjectType = $event->subjectType();
        $this->carbonFootprintId = $event->carbonFootprintId();
        $this->accountId = $event->accountId();
        $this->registeredAt = $event->occurredOn();
    }

    public function applyOrganizationalEmployeeParticipationUpdated(OrganizationalEmployeeParticipationUpdated $event): void
    {
        $this->totalEmployees = $event->totalEmployees();
        $this->confidenceLevel = $event->confidenceLevel();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyOrganizationalEmployeeParticipationAggregated(OrganizationalEmployeeParticipationAggregated $event): void
    {
        $this->answeredCount = $event->answeredCount();
        $this->marginOfError = $event->marginOfError();
        $this->aggregatedAt = $event->occurredOn();
        $this->updatedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
