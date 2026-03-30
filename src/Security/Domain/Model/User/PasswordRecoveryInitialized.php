<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\User;

use App\Security\Domain\DomainEvent;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PasswordRecoveryInitialized extends DomainEvent
{
    protected static string $eventName = 'passwordRecoveryInitialized';

    public function __construct(
        private readonly UserId $userId,
        private readonly string $username,
        private readonly RecoverToken $token,
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

    public function token(): RecoverToken
    {
        return $this->token;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->userId;
    }

    /**
     * @return array{userId: string, username: string, token: string, expirateAt: string}
     */
    public function payload(): array
    {
        return [
            'userId' => (string) $this->userId,
            'username' => $this->username,
            'token' => $this->token->token(),
            'expirateAt' => $this->token->expirateAt()?->format('Y-m-d H:i:s') ?? 'NOW',
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
        return new PasswordRecoveryInitialized(
            userId: new UserId($payload['userId'] ?? ''),
            username: $payload['username'] ?? '',
            token: new RecoverToken($payload['token'] ?? '', new \DateTimeImmutable($payload['expirateAt'] ?? 'NOW')),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
