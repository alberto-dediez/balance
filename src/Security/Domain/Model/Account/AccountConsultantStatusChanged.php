<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Account;

use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class AccountConsultantStatusChanged extends DomainEvent
{
    protected static string $eventName = 'accountConsultantStatusChanged';

    public function __construct(
        private readonly AccountId $accountId,
        private readonly bool $consultant,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
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
            'consultant' => $this->consultant,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new AccountConsultantStatusChanged(
            accountId: new AccountId($payload['accountId']),
            consultant: $payload['consultant'],
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
