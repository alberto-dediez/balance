<?php

namespace App\Security\Domain\Model\User;

use DateTimeImmutable;
use App\Security\Domain\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Security\Domain\Model\User\RecoverToken;

final class PasswordRecoveryInitialized extends DomainEvent
{
    protected static string $eventName = 'passwordRecoveryInitialized';

    public function __construct(
        private UserId $userId,
        private string $username,
        private RecoverToken $token,
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

    public function token(): RecoverToken
    {
        return $this->token;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->userId();
    }

    public function payload(): array
    {
        return [
            'userId' => (string) $this->userId,
            'username' => $this->username,
            'token' => $this->token->token(),
            'expirateAt' => $this->token->expirateAt()->format('Y-m-d H:i:s'),
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new PasswordRecoveryInitialized(
            userId: new UserId($payload['userId']),
            username: isset($payload['username']) ? $payload['username'] : '',
            token: new RecoverToken($payload['token'], new \DateTimeImmutable($payload['expirateAt'])),
            id: $id,
            occurredOn: $occurredOn
        );
    }
}
