<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Event;

use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class UserLogged extends InfrastructureEvent
{
    protected static string $eventName = 'userLogged';

    public function __construct(
        private readonly UserId $userId,
        private readonly string $firewall,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
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
        return $this->userId;
    }

    /** @return array{userId: string, firewall: string} */
    public function payload(): array
    {
        return [
            'userId' => (string) $this->userId,
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
        return new UserLogged(
            userId: new UserId($payload['userId'] ?? ''),
            firewall: $payload['firewall'] ?? '',
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
