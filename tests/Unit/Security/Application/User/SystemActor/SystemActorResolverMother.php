<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\User\SystemActor;

use App\Security\Application\User\SystemActor\SystemActorResolver;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\UserRepository;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;

class SystemActorResolverMother
{
    public static function withPaymentUser(): SystemActorResolver
    {
        $users = UserRepositoryMother::inMemory();
        $users->add(UserMother::fromUsernameAndRole(SystemActorResolver::SYSTEM_USERNAME, Role::Admin));

        return new SystemActorResolver($users);
    }

    public static function withoutUsers(): SystemActorResolver
    {
        return new SystemActorResolver(UserRepositoryMother::inMemory());
    }

    public static function from(UserRepository $users): SystemActorResolver
    {
        return new SystemActorResolver($users);
    }
}
