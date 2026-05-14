<?php

declare(strict_types=1);

namespace App\FoodLoss\Domain\Model\Protocol;

use App\FoodLoss\Domain\DomainEvent;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class ProtocolAccessed extends DomainEvent
{
    protected static string $eventName = 'protocolAccessed';

    public function __construct(
        private ProtocolId $protocolId,
        private AccountId $accountId,
        private UserId $userId,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function protocolId(): ProtocolId
    {
        return $this->protocolId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->protocolId();
    }

    public function payload(): array
    {
        return [
            'protocolId' => (string) $this->protocolId,
            'accountId' => (string) $this->accountId,
            'userId' => (string) $this->userId,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new ProtocolAccessed(
            protocolId: new ProtocolId($payload['protocolId']),
            accountId: new AccountId($payload['accountId']),
            userId: new UserId($payload['userId']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
