<?php

namespace App\Security\Domain\Model\Account;

use DateTimeImmutable;
use App\Security\Domain\DomainEvent;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;

final class AccountStatusChanged extends DomainEvent
{
    protected string $eventName = 'accountStatusChanged';

    public function __construct(
        private AccountId $accountId, 
        private bool $active, 
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function active(): string
    {
        return $this->active;
    }

    public function payload(): ?array
    {
        return [
            'accountId' => (string) $this->accountId,
            'active' => $this->active
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new AccountStatusChanged(
            accountId: new AccountId($payload['accountId']), 
            active: $payload['active'],
            id: $id,
            occurredOn: $occurredOn
        );
    }
}