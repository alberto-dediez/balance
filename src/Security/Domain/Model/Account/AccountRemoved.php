<?php

namespace App\Security\Domain\Model\Account;

use DateTimeImmutable;
use App\Security\Domain\DomainEvent;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;

final class AccountRemoved extends DomainEvent
{
    protected static string $eventName = 'accountRemoved';

    public function __construct(
        private AccountId $accountId, 
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function payload(): ?array
    {
        return [
            'accountId' => (string) $this->accountId
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new AccountRemoved(
            accountId: new AccountId($payload['accountId']),
            id: $id,
            occurredOn: $occurredOn
        );
    }
}