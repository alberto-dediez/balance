<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\User;

use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\RecoverToken;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;

class UserMother
{
    public static function registeredUser(): User
    {
        return self::from(
            userId: UserId::generate(),
            username: 'username',
        );
    }

    public static function systemUser(): User
    {
        return new User(
            id: new UserId('system'),
            username: 'system',
            roles: [Role::Admin, Role::SuperAdmin],
        );
    }

    public static function adminUser(): User
    {
        return self::from(
            userId: UserId::generate(),
            role: Role::Admin,
        );
    }

    public static function superAdminUser(): User
    {
        return self::from(
            userId: UserId::generate(),
            role: Role::SuperAdmin,
        );
    }

    public static function ownerUser(): User
    {
        return self::from(
            userId: UserId::generate(),
            role: Role::Owner,
        );
    }

    public static function fromUsername(string $username): User
    {
        return self::from(
            userId: UserId::generate(),
            username: $username,
        );
    }

    public static function fromUsernameAndRole(string $username, Role $role): User
    {
        return self::from(
            userId: UserId::generate(),
            username: $username,
            role: $role,
        );
    }

    public static function fromUsernameAndRoleAndId(string $username, Role $role, UserId $userId): User
    {
        return self::from(
            userId: $userId,
            username: $username,
            role: $role,
        );
    }

    public static function fromUsernameAndAccount(string $username, Account $account): User
    {
        return self::from(
            userId: UserId::generate(),
            username: $username,
            accountId: $account->id(),
            account: $account,
        );
    }

    public static function fromUsernameAndRoleAndAccount(string $username, Role $role, AccountId $accountId, ?Account $account = null): User
    {
        return self::from(
            userId: UserId::generate(),
            username: $username,
            role: $role,
            accountId: $accountId,
            account: $account,
        );
    }

    public static function fromUsernameAndRecoverToken(string $username, string $recoverToken): User
    {
        return self::from(
            userId: UserId::generate(),
            username: $username,
            recoverToken: new RecoverToken($recoverToken, new \DateTimeImmutable('NOW +1 HOUR')),
        );
    }

    public static function from(UserId $userId, string $username = 'username', Role $role = Role::User, ?AccountId $accountId = null, ?RecoverToken $recoverToken = null, ?Account $account = null): User
    {
        return new User(
            id: $userId,
            username: $username,
            password: 'apassword',
            accountId: $accountId,
            account: $account,
            roles: [
                $role,
            ],
            recoverToken: $recoverToken,
            registeredAt: new \DateTimeImmutable('NOW'),
        );
    }

    public static function random(): User
    {
        $roles = [
            Role::Admin,
            Role::Owner,
            Role::User,
        ];

        return self::fromUsernameAndRole('user-' . random_int(100, 9999999), $roles[random_int(0, 2)]);
    }
}
