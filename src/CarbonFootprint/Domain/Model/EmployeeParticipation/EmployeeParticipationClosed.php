<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmployeeParticipation;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EmployeeParticipationClosed extends DomainEvent
{
    protected static string $eventName = 'employeeParticipationClosed';

    public function __construct(
        private EmployeeParticipationId $employeeParticipationId,
        private float $marginOfError,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function employeeParticipationId(): EmployeeParticipationId
    {
        return $this->employeeParticipationId;
    }

    public function marginOfError(): float
    {
        return $this->marginOfError;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->employeeParticipationId();
    }

    public function payload(): array
    {
        return [
            'employeeParticipationId' => (string) $this->employeeParticipationId,
            'marginOfError' => $this->marginOfError,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            employeeParticipationId: new EmployeeParticipationId($payload['employeeParticipationId']),
            marginOfError: $payload['marginOfError'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
