<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Event;

use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class FailureLoginAttempted extends InfrastructureEvent
{
    protected static string $eventName = 'failureLoginAttempted';

    public function __construct(
        private readonly string $username,
        private readonly string $firewall,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
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

    /** @return array{username: string, firewall: string} */
    public function payload(): array
    {
        return [
            'username' => $this->username,
            'firewall' => $this->firewall,
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
        return new FailureLoginAttempted(
            username: $payload['username'] ?? '',
            firewall: $payload['firewall'] ?? '',
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
