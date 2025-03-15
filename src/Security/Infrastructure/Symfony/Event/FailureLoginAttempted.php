<?php

namespace App\Security\Infrastructure\Symfony\Event;

use DateTimeImmutable;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;

final class FailureLoginAttempted extends InfrastructureEvent
{
    protected string $eventName = 'failureLoginAttempted';

    public function __construct(
        private string $username, 
        private string $firewall,
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function username(): string
    {
        return $this->username;
    }

    public function firewall(): string
    {
        return $this->firewall;
    }

    public function payload(): ?array
    {
        return [
            'username' => $this->username,
            'firewall' => $this->firewall
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new FailureLoginAttempted(
            username: $payload['username'],
            firewall: $payload['firewall'],
            id: $id,
            occurredOn: $occurredOn
        );
    }
}