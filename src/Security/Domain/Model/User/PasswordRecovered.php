<?php

namespace App\Security\Domain\Model\User;

use DateTimeImmutable;
use App\Security\Domain\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;
use App\Security\Domain\Model\User\RecoverToken;

final class PasswordRecovered extends DomainEvent
{
    protected static string $eventName = 'passwordRecovered';

    public function __construct(
        private readonly UserId $userId,
        #[\SensitiveParameter]
        private readonly string $password,
        private readonly RecoverToken $token,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function password(): string
    {
        return $this->password;
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
     * @return array{userId: string, password: string, token: string, expirateAt: string}
     */
    public function payload(): array
    {
        return [
            'userId' => (string) $this->userId,
            'password' => $this->password,
            'token' => $this->token->token(),
            'expirateAt' => $this->token->expirateAt()?->format('Y-m-d H:i:s') ?? 'NOW',
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
        return new PasswordRecovered(
            userId: new UserId($payload['userId'] ?? ''),
            password: $payload['password'] ?? '',
            token: new RecoverToken($payload['token'] ?? '', new \DateTimeImmutable($payload['expirateAt'] ?? 'NOW')),
            id: $id,
            occurredOn: $occurredOn
        );
    }
}
