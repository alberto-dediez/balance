<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmployeeParticipation;

use App\CarbonFootprint\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EmployeeParticipationReopened extends DomainEvent
{
    protected static string $eventName = 'employeeParticipationReopened';

    public function __construct(
        private EmployeeParticipationId $employeeParticipationId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function employeeParticipationId(): EmployeeParticipationId
    {
        return $this->employeeParticipationId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->employeeParticipationId();
    }

    public function payload(): array
    {
        return [
            'employeeParticipationId' => (string) $this->employeeParticipationId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new self(
            employeeParticipationId: new EmployeeParticipationId($payload['employeeParticipationId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
