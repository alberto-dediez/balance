<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Account;

use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class AccountRegistered extends DomainEvent
{
    protected static string $eventName = 'accountRegistered';

    public function __construct(
        private readonly AccountId $accountId,
        private readonly string $name,
        private readonly bool $active,
        private readonly bool $consultant = false,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
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

    public function consultant(): bool
    {
        return $this->consultant;
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
            'active' => $this->active,
            'consultant' => $this->consultant,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new AccountRegistered(
            accountId: new AccountId($payload['accountId']),
            name: $payload['name'],
            active: $payload['active'],
            consultant: (bool) ($payload['consultant'] ?? false),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
