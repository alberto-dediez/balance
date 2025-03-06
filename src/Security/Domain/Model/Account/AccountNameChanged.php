<?php

namespace App\Security\Domain\Model\Account;

use DateTimeImmutable;
use App\Security\Domain\DomainEvent;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;

final class AccountNameChanged extends DomainEvent
{
    protected string $eventName = 'accountNameChanged';

    public function __construct(
        private AccountId $accountId, 
        private string $name, 
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function payload(): ?array
    {
        return [
            'accountId' => (string) $this->accountId,
            'name' => $this->name
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new AccountNameChanged(
            accountId: new AccountId($payload['accountId']), 
            name: $payload['name'],
            id: $id,
            occurredOn: $occurredOn
        );
    }
}