<?php

declare(strict_types=1);

namespace App\Shared\Application\Live;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Shared\Application\CurrentRoles\CurrentRolesProvider;

final class LiveTopics
{
    public const string ADMIN = '/live/admin';

    public function __construct(private readonly CurrentRolesProvider $currentRolesProvider) {}

    public static function ofUser(UserId $id): string
    {
        return '/live/users/' . $id;
    }

    /**
     * @return list<string>
     */
    public function for(User $user): array
    {
        $currentRoles = $this->currentRolesProvider->currentRoles($user);

        $hasRole = static fn(Role $role): bool => null === $currentRoles
            ? $user->hasRole($role)
            : in_array($role, $currentRoles, true);

        if ($hasRole(Role::SuperAdmin) || $hasRole(Role::Admin)) {
            return [self::ADMIN, self::ofUser($user->id())];
        }

        return [self::ofUser($user->id())];
    }
}
