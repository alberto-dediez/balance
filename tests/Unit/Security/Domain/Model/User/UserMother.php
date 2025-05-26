<?php

namespace App\Tests\Unit\Security\Domain\Model\User;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;
use App\Security\Domain\Model\Account\Account;
use App\Security\Domain\Model\Account\AccountId;

class UserMother
{
    public static function registeredUser()
    {   
        return self::from(
            userId: UserId::generate(), 
            username: 'username'
        );
    }

    public static function systemUser()
    {
        return new User(
            id: new UserId('system'), 
            username: 'system',
            roles: [Role::Admin]
        );
    }

    public static function adminUser()
    {
        return self::from(
            userId: UserId::generate(), 
            role: Role::Admin
        );
    }

    public static function ownerUser()
    {
        return self::from(
            userId: UserId::generate(), 
            role: Role::Owner
        );
    }

    public static function fromUsername($username)
    {
        return self::from(
            userId: UserId::generate(), 
            username: $username
        );
    }

    public static function fromUsernameAndRole($username, Role $role)
    {
        return self::from(
            userId: UserId::generate(), 
            role: $role,
            username: $username
        );
    }

    public static function fromUsernameAndRoleAndId($username, Role $role, UserId $userId)
    {
        return self::from(
            userId: $userId, 
            username: $username
        );
    }

    public static function fromUsernameAndAccount($username, Account $account)
    {
        return self::from(
            userId: UserId::generate(), 
            username: $username, 
            accountId: $account->id()
        );
    }

    public static function fromUsernameAndRoleAndAccount(string $username, Role $role, AccountId $accountId)
    {
        return self::from(
            userId: UserId::generate(), 
            username: $username, 
            role: $role, 
            accountId: $accountId
        );
    }

    public static function from(UserId $userId, string $username = 'username', Role $role = Role::User, ?AccountId $accountId = null)
    {
        $user = new User($userId);
        $user->register($username, 'apassword', $accountId);
        $user->grantRole($role);

        return $user;
    }

    public static function random()
    {
        $roles = [
            Role::Admin,
            Role::User
        ];

        return self::fromUsernameAndRole('user-' . rand(100, 999), $roles[rand(0, 1)]);
    }
}