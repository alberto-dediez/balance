<?php

namespace App\Security\Domain\Model\User;

use DateTimeImmutable;
use App\Security\Domain\DomainEvent;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class RoleGranted extends DomainEvent
{
    protected static string $eventName = 'roleGranted';

    public function __construct(
        private readonly UserId $userId,
        private readonly Role $role,
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

    public function aggregateRootId(): AggregateRootId
    {
        return $this->userId;
    }

    /**
     * @return array{userId: string, role: string}
     */
    public function payload(): array
    {
        return [
            'userId' => (string) $this->userId,
            'role' => $this->role->value
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function rebuildFromPayload(
        DomainEventId     $id,
        DateTimeImmutable $occurredOn,
        array             $payload = []
    ): static {
        $role = Role::tryFrom($payload['role'] ?? '');

        assert($role instanceof Role);

        return new RoleGranted(
            userId: new UserId($payload['userId'] ?? ''),
            role: $role,
            id: $id,
            occurredOn: $occurredOn
        );
    }
}
