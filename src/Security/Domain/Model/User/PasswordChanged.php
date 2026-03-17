<?php

namespace App\Security\Domain\Model\User;

use DateTimeImmutable;
use App\Security\Domain\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class PasswordChanged extends DomainEvent
{
    protected static string $eventName = 'passwordChanged';

    public function __construct(
        private readonly UserId $userId,
        #[\SensitiveParameter]
        private readonly string $password,
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

    public function aggregateRootId(): AggregateRootId
    {
        return $this->userId;
    }

    public function payload(): array
    {
        return [
            'userId' => (string) $this->userId,
            'password' => $this->password
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new PasswordChanged(
            userId: new UserId($payload['userId']),
            password: $payload['password'],
            id: $id,
            occurredOn: $occurredOn
        );
    }
}
