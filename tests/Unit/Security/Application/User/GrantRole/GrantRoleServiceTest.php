<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\User\GrantRole;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\GrantRole\GrantRoleRequest;
use App\Security\Application\User\GrantRole\GrantRoleService;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\RoleGranted;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\User\UserAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class GrantRoleServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $admin = UserMother::adminUser();

        $user = UserMother::registeredUser();

        $repository->add($user);

        $request = new GrantRoleRequest(
            userId: $user->id(),
            role: Role::Admin,
        );

        $result = $sut($request, $admin);

        $this->assertTrue($user->id()->equals($result->userId));
        $this->assertCount(2, $result->roles);

        $userPersisted = $repository->find($result->userId);

        $this->assertNotNull($userPersisted);
        $this->assertCount(2, $userPersisted->roles());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof RoleGranted);
    }

    public function testOwnerCanGrantRoleForUserOfSameAccount(): void
    {
        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $account = AccountMother::random();

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $account->id());
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, $account->id());

        $repository->add($user);

        $request = new GrantRoleRequest(
            userId: $user->id(),
            role: Role::Owner,
        );

        $result = $sut($request, $owner);

        $this->assertTrue($user->id()->equals($result->userId));
        $this->assertCount(2, $result->roles);

        $userPersisted = $repository->find($result->userId);

        $this->assertNotNull($userPersisted);
        $this->assertCount(2, $userPersisted->roles());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof RoleGranted);
    }

    public function testOwnerCannotGrantRoleAdmin(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $account = AccountMother::random();

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $account->id());
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, $account->id());

        $repository->add($user);

        $request = new GrantRoleRequest(
            userId: $user->id(),
            role: Role::Admin,
        );

        $sut($request, $owner);
    }

    public function testUnauthorizedAccess(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $performBy = UserMother::ownerUser();

        $user = UserMother::registeredUser();

        $repository->add($user);

        $request = new GrantRoleRequest(
            userId: $user->id(),
            role: Role::Admin,
        );

        $sut($request, $performBy);
    }

    public function testAdminCannotGrantSuperAdminRole(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $admin = UserMother::adminUser();

        $user = UserMother::registeredUser();

        $repository->add($user);

        $request = new GrantRoleRequest(
            userId: $user->id(),
            role: Role::SuperAdmin,
        );

        $sut($request, $admin);
    }

    public function testSystemUserCanGrantSuperAdminRole(): void
    {
        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $system = UserMother::systemUser();

        $user = UserMother::registeredUser();

        $repository->add($user);

        $request = new GrantRoleRequest(
            userId: $user->id(),
            role: Role::SuperAdmin,
        );

        $result = $sut($request, $system);

        $userPersisted = $repository->find($result->userId);

        $this->assertNotNull($userPersisted);
        $this->assertTrue($userPersisted->hasRole(Role::SuperAdmin));
    }

    public function testIsHandle(): void
    {
        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $user = UserMother::random();

        $result = $sut->isHandled(new GrantRoleRequest(
            userId: $user->id(),
            role: Role::Admin,
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryUserRepository $repository): GrantRoleService
    {
        $authorizer = UserAuthorizerMother::create();

        return new GrantRoleService($repository, $authorizer);
    }
}
