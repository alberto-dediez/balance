<?php

namespace App\Security\Domain\Model\User;

use DateTimeImmutable;
use App\Security\Domain\DomainEvent;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class UserRemoved extends DomainEvent
{
    protected static string $eventName = 'userRemoved';

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

    public function aggregateRootId(): ?AggregateRootId
    {
        return $this->userId();
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
        return new UserRemoved(
            userId: new UserId($payload['userId']),
            id: $id,
            occurredOn: $occurredOn
        );
    }
}