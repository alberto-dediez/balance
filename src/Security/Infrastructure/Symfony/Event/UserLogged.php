<?php

namespace App\Security\Infrastructure\Symfony\Event;

use DateTimeImmutable;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class UserLogged extends InfrastructureEvent
{
    protected static string $eventName = 'userLogged';

    public function __construct(
        private UserId $userId,
        private string $firewall,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function firewall(): string
    {
        return $this->firewall;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->userId();
    }

    public function payload(): array
    {
        return [
            'userId' => (string) $this->userId,
            'firewall' => $this->firewall
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new UserLogged(
            userId: new UserId($payload['userId']),
            firewall: isset($payload['firewall']) ? $payload['firewall'] : '',
            id: $id,
            occurredOn: $occurredOn
        );
    }
}
