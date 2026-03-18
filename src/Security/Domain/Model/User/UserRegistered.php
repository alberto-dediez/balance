<?php

namespace App\Security\Domain\Model\User;

use DateTimeImmutable;
use App\Security\Domain\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Security\Domain\Model\Account\AccountId;

final class UserRegistered extends DomainEvent
{
    protected static string $eventName = 'userRegistered';

    public function __construct(
        private readonly UserId $userId,
        private readonly string $username,
        #[\SensitiveParameter]
        private readonly string $password,
        private readonly ?AccountId $accountId = null,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->userId;
    }

    /**
     * @return array{userId:string, username: string, password: string, accountId: string}
     */
    public function payload(): array
    {
        return [
            'userId' => (string) $this->userId,
            'username' => $this->username,
            'password' => $this->password,
            'accountId' => (string) $this->accountId
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function rebuildFromPayload(
        DomainEventId     $id,
        DateTimeImmutable $occurredOn,
        array             $payload = []
    ): static {
        return new UserRegistered(
            userId: new UserId($payload['userId'] ?? ''),
            username: $payload['username'] ?? '',
            password: $payload['password'] ?? '',
            accountId: (!empty($payload['accountId']))
                ? new AccountId($payload['accountId'])
                : null,
            id: $id,
            occurredOn: $occurredOn
        );
    }
}
