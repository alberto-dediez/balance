<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\OrganizationalEmployeeParticipation;

use App\CarbonFootprint\Domain\DomainEvent;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class OrganizationalEmployeeParticipationUpdated extends DomainEvent
{
    protected static string $eventName = 'organizationalEmployeeParticipationUpdated';

    public function __construct(
        private OrganizationalEmployeeParticipationId $participationId,
        private ?int $totalEmployees,
        private ?ConfidenceLevel $confidenceLevel,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function participationId(): OrganizationalEmployeeParticipationId
    {
        return $this->participationId;
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
        return $this->participationId;
    }

    public function payload(): array
    {
        return [
            'participationId' => (string) $this->participationId,
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
            participationId: new OrganizationalEmployeeParticipationId($payload['participationId']),
            totalEmployees: $payload['totalEmployees'] ?? null,
            confidenceLevel: isset($payload['confidenceLevel']) ? ConfidenceLevel::from($payload['confidenceLevel']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
