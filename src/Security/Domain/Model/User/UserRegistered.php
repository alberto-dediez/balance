<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\User;

use App\Security\Domain\DomainEvent;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class UserRegistered extends DomainEvent
{
    protected static string $eventName = 'userRegistered';

    public function __construct(
        private readonly UserId $userId,
        private readonly string $username,
        #[\SensitiveParameter]
        private readonly string $password,
        private readonly ?AccountId $accountId = null,
        private readonly bool $welcome = true,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
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

    public function welcome(): bool
    {
        return $this->welcome;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->userId;
    }

    /**
     * @return array{userId:string, username: string, password: string, accountId: string, welcome: bool}
     */
    public function payload(): array
    {
        return [
            'userId' => (string) $this->userId,
            'username' => $this->username,
            'password' => $this->password,
            'accountId' => (string) $this->accountId,
            'welcome' => $this->welcome,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new UserRegistered(
            userId: new UserId($payload['userId'] ?? ''),
            username: $payload['username'] ?? '',
            password: $payload['password'] ?? '',
            accountId: (empty($payload['accountId']))
                ? null
                : new AccountId($payload['accountId']),
            welcome: (bool) ($payload['welcome'] ?? true),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
