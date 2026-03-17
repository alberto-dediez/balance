<?php

namespace App\Security\Domain\Model\Account;

use DateTimeImmutable;
use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Security\Domain\Model\Account\AccountId;

final class AccountStatusChanged extends DomainEvent
{
    protected static string $eventName = 'accountStatusChanged';

    public function __construct(
        private readonly AccountId $accountId,
        private readonly bool $active,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function active(): bool
    {
        return $this->active;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->accountId;
    }

    public function payload(): array
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
