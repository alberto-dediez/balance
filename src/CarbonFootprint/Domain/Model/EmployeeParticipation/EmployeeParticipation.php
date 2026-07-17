<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmployeeParticipation;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class EmployeeParticipation implements \Stringable
{
    use AggregateRoot;

    /** @param array<DomainEvent> $events */
    public function __construct(
        private EmployeeParticipationId $id,
        private ?EventCarbonFootprintId $eventCarbonFootprintId = null,
        private ?AccountId $accountId = null,
        private EmployeeParticipationStatus $status = EmployeeParticipationStatus::Open,
        private ?int $totalEmployees = null,
        private ?ConfidenceLevel $confidenceLevel = null,
        private ?float $marginOfError = null,
        private ?\DateTimeImmutable $closedAt = null,
        array $events = [],
        private \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        private ?\DateTimeImmutable $updatedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): EmployeeParticipationId
    {
        return $this->id;
    }

    public function eventCarbonFootprintId(): ?EventCarbonFootprintId
    {
        return $this->eventCarbonFootprintId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function status(): EmployeeParticipationStatus
    {
        return $this->status;
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

    public function closedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
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
        EventCarbonFootprintId $eventCarbonFootprintId,
        AccountId $accountId,
    ): void {
        $this->recordApplyAndPublish(
            new EmployeeParticipationRegistered(
                employeeParticipationId: $this->id(),
                eventCarbonFootprintId: $eventCarbonFootprintId,
                accountId: $accountId,
            ),
        );
    }

    public function update(?int $totalEmployees, ?ConfidenceLevel $confidenceLevel): void
    {
        if ($this->totalEmployees === $totalEmployees && $this->confidenceLevel === $confidenceLevel) {
            return;
        }

        $this->recordApplyAndPublish(
            new EmployeeParticipationUpdated(
                employeeParticipationId: $this->id(),
                totalEmployees: $totalEmployees,
                confidenceLevel: $confidenceLevel,
            ),
        );
    }

    public function close(float $marginOfError): void
    {
        $this->recordApplyAndPublish(
            new EmployeeParticipationClosed(
                employeeParticipationId: $this->id(),
                marginOfError: $marginOfError,
            ),
        );
    }

    public function reopen(): void
    {
        if (!$this->status->isClosed()) {
            return;
        }

        $this->recordApplyAndPublish(
            new EmployeeParticipationReopened(
                employeeParticipationId: $this->id(),
            ),
        );
    }

    public function applyEmployeeParticipationRegistered(EmployeeParticipationRegistered $event): void
    {
        $this->eventCarbonFootprintId = $event->eventCarbonFootprintId();
        $this->accountId = $event->accountId();
        $this->status = EmployeeParticipationStatus::Open;
        $this->registeredAt = $event->occurredOn();
    }

    public function applyEmployeeParticipationUpdated(EmployeeParticipationUpdated $event): void
    {
        $this->totalEmployees = $event->totalEmployees();
        $this->confidenceLevel = $event->confidenceLevel();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyEmployeeParticipationClosed(EmployeeParticipationClosed $event): void
    {
        $this->status = EmployeeParticipationStatus::Closed;
        $this->marginOfError = $event->marginOfError();
        $this->closedAt = $event->occurredOn();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyEmployeeParticipationReopened(EmployeeParticipationReopened $event): void
    {
        $this->status = EmployeeParticipationStatus::Open;
        $this->marginOfError = null;
        $this->closedAt = null;
        $this->updatedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
