<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\User\UngrantRole;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\UngrantRole\UngrantRoleRequest;
use App\Security\Application\User\UngrantRole\UngrantRoleService;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\RoleUngranted;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\User\UserAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UngrantRoleServiceTest extends TestCase
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

        $performBy = UserMother::adminUser();

        $user = UserMother::adminUser();

        $repository->add($user);

        $request = new UngrantRoleRequest(
            userId: $user->id(),
            role: Role::Admin,
        );

        $result = $sut($request, $performBy);

        $this->assertTrue($user->id()->equals($result->userId));
        $this->assertCount(1, $result->roles);

        $userPersisted = $repository->find($result->userId);

        $this->assertNotNull($userPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof RoleUngranted);
    }

    public function testOwnerCanGrantRoleForUserOfSameAccount(): void
    {
        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $account = AccountMother::random();

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $account->id());
        $user = UserMother::fromUsernameAndRoleAndAccount('other-owner', Role::Owner, $account->id());

        $repository->add($user);

        $request = new UngrantRoleRequest(
            userId: $user->id(),
            role: Role::Owner,
        );

        $result = $sut($request, $owner);

        $this->assertTrue($user->id()->equals($result->userId));
        $this->assertCount(1, $result->roles);

        $userPersisted = $repository->find($result->userId);

        $this->assertNotNull($userPersisted);
        $this->assertCount(1, $userPersisted->roles());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof RoleUngranted);
    }

    public function testOwnerCannotGrantRoleAdmin(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $account = AccountMother::random();

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $account->id());
        $user = UserMother::fromUsernameAndRoleAndAccount('admin', Role::Admin, $account->id());

        $repository->add($user);

        $request = new UngrantRoleRequest(
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

        $user = UserMother::ownerUser();

        $repository->add($user);

        $request = new UngrantRoleRequest(
            userId: $user->id(),
            role: Role::Admin,
        );

        $sut($request, $user);
    }

    public function testAdminCannotUngrantSuperAdminRole(): void
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $admin = UserMother::adminUser();

        $user = UserMother::superAdminUser();

        $repository->add($user);

        $request = new UngrantRoleRequest(
            userId: $user->id(),
            role: Role::SuperAdmin,
        );

        $sut($request, $admin);
    }

    public function testSuperAdminCanUngrantSuperAdminRole(): void
    {
        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $performBy = UserMother::superAdminUser();

        $user = UserMother::superAdminUser();

        $repository->add($user);

        $request = new UngrantRoleRequest(
            userId: $user->id(),
            role: Role::SuperAdmin,
        );

        $result = $sut($request, $performBy);

        $userPersisted = $repository->find($result->userId);

        $this->assertNotNull($userPersisted);
        $this->assertFalse($userPersisted->hasRole(Role::SuperAdmin));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof RoleUngranted);
    }

    public function testIsHandle(): void
    {
        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $user = UserMother::random();

        $result = $sut->isHandled(new UngrantRoleRequest(
            userId: $user->id(),
            role: Role::Admin,
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryUserRepository $repository): UngrantRoleService
    {
        return new UngrantRoleService($repository, UserAuthorizerMother::create());
    }
}
