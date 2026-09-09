<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Account;

use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class AccountConsultantChanged extends DomainEvent
{
    protected static string $eventName = 'accountConsultantChanged';

    public function __construct(
        private readonly AccountId $accountId,
        private readonly ?AccountId $consultantAccountId = null,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function consultantAccountId(): ?AccountId
    {
        return $this->consultantAccountId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->accountId;
    }

    public function payload(): array
    {
        return [
            'accountId' => (string) $this->accountId,
            'consultantAccountId' => $this->consultantAccountId instanceof AccountId ? (string) $this->consultantAccountId : null,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new AccountConsultantChanged(
            accountId: new AccountId($payload['accountId']),
            consultantAccountId: null !== ($payload['consultantAccountId'] ?? null) ? new AccountId($payload['consultantAccountId']) : null,
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
