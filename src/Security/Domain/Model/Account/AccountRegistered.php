<?php

namespace App\Security\Domain\Model\Account;

use DateTimeImmutable;
use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Security\Domain\Model\Account\AccountId;

final class AccountRegistered extends DomainEvent
{
    protected static string $eventName = 'accountRegistered';

    public function __construct(
        private readonly AccountId $accountId,
        private readonly string $name,
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

    public function name(): string
    {
        return $this->name;
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
            'name' => $this->name,
            'active' => $this->active
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new AccountRegistered(
            accountId: new AccountId($payload['accountId']),
            name: $payload['name'],
            active: $payload['active'],
            id: $id,
            occurredOn: $occurredOn
        );
    }
}
