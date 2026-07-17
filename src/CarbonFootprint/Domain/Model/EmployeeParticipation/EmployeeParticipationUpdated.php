<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmployeeParticipation;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EmployeeParticipationUpdated extends DomainEvent
{
    protected static string $eventName = 'employeeParticipationUpdated';

    public function __construct(
        private EmployeeParticipationId $employeeParticipationId,
        private ?int $totalEmployees,
        private ?ConfidenceLevel $confidenceLevel,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function employeeParticipationId(): EmployeeParticipationId
    {
        return $this->employeeParticipationId;
    }

    public function totalEmployees(): ?int
    {
        return $this->totalEmployees;
    }

    public function confidenceLevel(): ?ConfidenceLevel
    {
        return $this->confidenceLevel;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->employeeParticipationId();
    }

    public function payload(): array
    {
        return [
            'employeeParticipationId' => (string) $this->employeeParticipationId,
            'totalEmployees' => $this->totalEmployees,
            'confidenceLevel' => $this->confidenceLevel?->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            employeeParticipationId: new EmployeeParticipationId($payload['employeeParticipationId']),
            totalEmployees: $payload['totalEmployees'] ?? null,
            confidenceLevel: isset($payload['confidenceLevel']) ? ConfidenceLevel::from($payload['confidenceLevel']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
