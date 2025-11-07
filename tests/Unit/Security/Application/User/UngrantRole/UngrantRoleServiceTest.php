<?php

namespace App\Tests\Unit\Security\Application\User\UngrantRole;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\UngrantRole\UngrantRoleRequest;
use App\Security\Application\User\UngrantRole\UngrantRoleResponse;
use App\Security\Application\User\UngrantRole\UngrantRoleService;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\RoleUngranted;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\User\UserAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UngrantRoleServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke()
    {
        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $performBy = UserMother::adminUser();

        $user = UserMother::adminUser();

        $repository->add($user);

        $request = new UngrantRoleRequest(
            userId: $user->id(),
            role: Role::Admin
        );

        $result = $sut($request, $performBy);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof UngrantRoleResponse);
        $this->assertTrue($user->id()->equals($result->userId));
        $this->assertCount(1, $result->roles);

        $userPersisted = $repository->find($result->userId);

        $this->assertNotNull($userPersisted);
        $this->assertTrue($userPersisted instanceof User);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof RoleUngranted);
    }

    public function testOwnerCanGrantRoleForUserOfSameAccount()
    {
        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $account = AccountMother::random();

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $account->id());
        $user = UserMother::fromUsernameAndRoleAndAccount('other-owner', Role::Owner, $account->id());

        $repository->add($user);

        $request = new UngrantRoleRequest(
            userId: $user->id(),
            role: Role::Owner
        );

        $result = $sut($request, $owner);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof UngrantRoleResponse);
        $this->assertTrue($user->id()->equals($result->userId));
        $this->assertCount(1, $result->roles);

        $userPersisted = $repository->find($result->userId);

        $this->assertNotNull($userPersisted);
        $this->assertTrue($userPersisted instanceof User);
        $this->assertCount(1, $userPersisted->roles());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof RoleUngranted);
    }

    public function testOwnerCannotGrantRoleAdmin()
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
            role: Role::Admin
        );

        $sut($request, $owner);
    }

    public function testUnauthorizedAccess()
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $user = UserMother::ownerUser();

        $repository->add($user);

        $request = new UngrantRoleRequest(
            userId: $user->id(),
            role: Role::Admin
        );

        $sut($request, $user);
    }

    public function testIsHandle()
    {
        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $user = UserMother::random();

        $result = $sut->isHandled(new UngrantRoleRequest(
            userId: $user->id(),
            role: Role::Admin
        ));

        $this->assertTrue($result);
    }

    private function createSUT($repository)
    {
        return new UngrantRoleService($repository, UserAuthorizerMother::create());
    }
}
