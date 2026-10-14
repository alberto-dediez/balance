<?php

declare(strict_types=1);

namespace App\HumanResources\Domain\Model\Employee;

use App\HumanResources\Domain\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class EmployeeAndUserLinked extends DomainEvent
{
    protected static string $eventName = 'employeeAndUserLinked';

    public function __construct(
        private EmployeeId $employeeId,
        private UserId $userId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function employeeId(): EmployeeId
    {
        return $this->employeeId;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->employeeId();
    }

    public function payload(): array
    {
        return [
            'employeeId' => (string) $this->employeeId,
            'userId' => (string) $this->userId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new EmployeeAndUserLinked(
            employeeId: new EmployeeId($payload['employeeId']),
            userId: new UserId($payload['userId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
