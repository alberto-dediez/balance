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
        $user = self::registeredUser();
        $user->grantRole(Role::Admin);

        return $user;
    }

    public static function fromUsername($username)
    {
        $userId = UserId::generate();

        $user = new User($userId);
        $user->register($username, 'password');

        return $user;
    }

    public static function fromUsernameAndRole($username, Role $role)
    {
        $userId = UserId::generate();

        $user = new User($userId);
        $user->register($username, 'password');
        $user->grantRole($role);

        return $user;
    }
}