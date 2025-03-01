<?php

namespace App\Tests\Unit\Security\Domain\Model\User;

use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\UserId;

class UserMother
{
    public static function registeredUser()
    {   
        return self::fromUsername('username');
    }

    public static function adminUser()
    {
        return self::fromUsernameAndRole('username', Role::Admin);
    }

    public static function fromUsername($username)
    {
        $userId = UserId::generate();

        return self::fromUsernameAndRoleAndId($username, Role::User, $userId);
    }

    public static function fromUsernameAndRole($username, Role $role)
    {
        $userId = UserId::generate();

        return self::fromUsernameAndRoleAndId($username, $role, $userId);
    }

    public static function fromUsernameAndRoleAndId($username, Role $role, UserId $userId)
    {
        $user = new User($userId);
        $user->register($username, 'password');
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