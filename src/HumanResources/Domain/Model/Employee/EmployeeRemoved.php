<?php

declare(strict_types=1);

namespace App\HumanResources\Domain\Model\Employee;

use App\HumanResources\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EmployeeRemoved extends DomainEvent
{
    protected static string $eventName = 'employeeRemoved';

    public function __construct(
        private EmployeeId $employeeId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function employeeId(): EmployeeId
    {
        return $this->employeeId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->employeeId();
    }

    public function payload(): array
    {
        return [
            'employeeId' => (string) $this->employeeId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new EmployeeRemoved(
            employeeId: new EmployeeId($payload['employeeId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
