<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Account;

use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class AccountDistributorStatusChanged extends DomainEvent
{
    protected static string $eventName = 'accountDistributorStatusChanged';

    public function __construct(
        private readonly AccountId $accountId,
        private readonly bool $distributor,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function distributor(): bool
    {
        return $this->distributor;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->accountId;
    }

    public function payload(): array
    {
        return [
            'accountId' => (string) $this->accountId,
            'distributor' => $this->distributor,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new AccountDistributorStatusChanged(
            accountId: new AccountId($payload['accountId']),
            distributor: $payload['distributor'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
