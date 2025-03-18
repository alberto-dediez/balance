<?php

namespace App\Security\Domain\Model\User;

use DateTimeImmutable;
use App\Security\Domain\DomainEvent;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;

final class RoleGranted extends DomainEvent
{
    protected static string $eventName = 'roleGranted';

    public function __construct(
        private UserId $userId, 
        private Role $role, 
        ?DomainEventId $id = null,
        ?DateTimeImmutable $occurredOn = null
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function role(): Role
    {
        return $this->role;
    }

    public function payload(): ?array
    {
        return [
            'userId' => (string) $this->userId,
            'role' => $this->role->value
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        DateTimeImmutable $occurredOn,
        ?array $payload
    ): static {
        return new RoleGranted(
            userId: new UserId($payload['userId']), 
            role: Role::tryFrom($payload['role']),
            id: $id,
            occurredOn: $occurredOn
        );
    }
}