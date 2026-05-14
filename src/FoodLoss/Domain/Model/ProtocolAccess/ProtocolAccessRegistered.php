<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\ProtocolAccess;

use App\FoodLoss\Domain\DomainEvent;
use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\HumanResources\Domain\Model\Employee\EmployeeId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ProtocolAccessRegistered extends DomainEvent
{
    protected static string $eventName = 'protocolAccessRegistered';

    public function __construct(
        private ProtocolAccessId $protocolAccessId,
        private ProtocolId $protocolId,
        private EmployeeId $employeeId,
        private AccountId $accountId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function protocolAccessId(): ProtocolAccessId
    {
        return $this->protocolAccessId;
    }

    public function protocolId(): ProtocolId
    {
        return $this->protocolId;
    }

    public function employeeId(): EmployeeId
    {
        return $this->employeeId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->protocolAccessId();
    }

    public function payload(): array
    {
        return [
            'protocolAccessId' => (string) $this->protocolAccessId,
            'protocolId' => (string) $this->protocolId,
            'employeeId' => (string) $this->employeeId,
            'accountId' => (string) $this->accountId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ProtocolAccessRegistered(
            protocolAccessId: new ProtocolAccessId($payload['protocolAccessId']),
            protocolId: new ProtocolId($payload['protocolId']),
            employeeId: new EmployeeId($payload['employeeId']),
            accountId: new AccountId($payload['accountId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
