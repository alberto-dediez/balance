<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\ProtocolAccess;

use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class ProtocolAccess implements \Stringable
{
    use AggregateRoot;

    /** @param array<DomainEvent> $events */
    public function __construct(
        private ProtocolAccessId $id,
        private ?ProtocolId $protocolId = null,
        private ?EmployeeId $employeeId = null,
        private ?AccountId $accountId = null,
        array $events = [],
        private \DateTimeImmutable $accessedAt = new \DateTimeImmutable('NOW'),
    ) {
        $this->events = $events;
    }

    public function id(): ProtocolAccessId
    {
        return $this->id;
    }

    public function protocolId(): ?ProtocolId
    {
        return $this->protocolId;
    }

    public function employeeId(): ?EmployeeId
    {
        return $this->employeeId;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function accessedAt(): \DateTimeImmutable
    {
        return $this->accessedAt;
    }

    public function register(
        ProtocolId $protocolId,
        EmployeeId $employeeId,
        AccountId $accountId,
    ): void {
        $this->recordApplyAndPublish(
            new ProtocolAccessRegistered(
                protocolAccessId: $this->id(),
                protocolId: $protocolId,
                employeeId: $employeeId,
                accountId: $accountId,
            ),
        );
    }

    public function applyProtocolAccessRegistered(ProtocolAccessRegistered $event): void
    {
        $this->protocolId = $event->protocolId();
        $this->employeeId = $event->employeeId();
        $this->accountId = $event->accountId();
        $this->accessedAt = $event->occurredOn();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
