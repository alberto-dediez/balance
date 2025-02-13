<?php

namespace App\Security\Domain\Model\User;

use DateTimeImmutable;
use App\Security\Domain\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;

final class UserRegistered extends DomainEvent
{
    protected string $eventName = 'userRegistered';

    public function __construct(
        private UserId $userId, 
        private string $username, 
        private string $password,
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

    public function payload(): ?array
    {
        return [
            'userId' => (string) $this->userId,
            'username' => $this->username,
            'password' => $this->password
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new UserRegistered(
            userId: new UserId($payload['userId']), 
            username: $payload['username'],
            password: $payload['password'],
            id: $id,
            occurredOn: $occurredOn
        );
    }
}