<?php

namespace App\Security\Infrastructure\Symfony\Event;

use DateTimeImmutable;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;

final class UserLoggedOut extends InfrastructureEvent
{
    protected static string $eventName = 'userLoggedOut';

    public function __construct(
        private UserId $userId,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function payload(): ?array
    {
        return [
            'userId' => (string) $this->userId
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new UserLoggedOut(
            userId: new UserId($payload['userId']),
            id: $id,
            occurredOn: $occurredOn
        );
    }
}