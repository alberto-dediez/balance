<?php

namespace App\Security\Infrastructure\Symfony\Event;

use DateTimeImmutable;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class FailureLoginAttempted extends InfrastructureEvent
{
    protected static string $eventName = 'failureLoginAttempted';

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

    public function aggregateRootId(): ?AggregateRootId
    {
        return null;
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
            firewall: isset($payload['firewall']) ? $payload['firewall'] : '',
            id: $id,
            occurredOn: $occurredOn
        );
    }
}