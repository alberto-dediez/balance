<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\User\SystemActor;

use App\Security\Application\User\SystemActor\SystemActorNotFoundException;
use App\Security\Application\User\SystemActor\SystemActorResolver;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\User\UserRepositoryMother;
use PHPUnit\Framework\TestCase;

class SystemActorResolverTest extends TestCase
{
    public function testThePaymentUserIsPreferredOverAnyAdministrator(): void
    {
        $users = UserRepositoryMother::inMemory();
        $users->add(UserMother::fromUsernameAndRole('root', Role::SuperAdmin));
        $users->add(UserMother::fromUsernameAndRole('boss', Role::Admin));
        $users->add(UserMother::fromUsernameAndRole(SystemActorResolver::SYSTEM_USERNAME, Role::Admin));

        $this->assertSame(SystemActorResolver::SYSTEM_USERNAME, (new SystemActorResolver($users))()->username());
    }

    public function testAnAdminStandsInWhenThereIsNoPaymentUser(): void
    {
        $users = UserRepositoryMother::inMemory();
        $users->add(UserMother::fromUsernameAndRole('root', Role::SuperAdmin));
        $users->add(UserMother::fromUsernameAndRole('boss', Role::Admin));

        $this->assertSame('boss', (new SystemActorResolver($users))()->username());
    }

    public function testASuperAdminIsTheLastResort(): void
    {
        $users = UserRepositoryMother::inMemory();
        $users->add(UserMother::fromUsernameAndRole('someone', Role::User));
        $users->add(UserMother::fromUsernameAndRole('root', Role::SuperAdmin));

        $this->assertSame('root', (new SystemActorResolver($users))()->username());
    }

    public function testItFailsWhenNobodyCanPerformTheWork(): void
    {
        $users = UserRepositoryMother::inMemory();
        $users->add(UserMother::fromUsernameAndRole('someone', Role::User));

        $this->expectException(SystemActorNotFoundException::class);

        (new SystemActorResolver($users))();
    }
}
