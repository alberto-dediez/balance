<?php

namespace App\Tests\Unit\Security\Application\User\GrantRole;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\GrantRole\GrantRoleRequest;
use App\Security\Application\User\GrantRole\GrantRoleResponse;
use App\Security\Application\User\GrantRole\GrantRoleService;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\RoleGranted;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\User\UserAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class GrantRoleServiceTest extends TestCase
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

        $admin = UserMother::adminUser();

        $user = UserMother::registeredUser();

        $repository->add($user);

        $request = new GrantRoleRequest(
            userId: $user->id(),
            role: Role::Admin
        );

        $result = $sut($request, $admin);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof GrantRoleResponse);
        $this->assertTrue($user->id()->equals($result->userId));
        $this->assertCount(2, $result->roles);

        $userPersisted = $repository->find($result->userId);

        $this->assertNotNull($userPersisted);
        $this->assertTrue($userPersisted instanceof User);
        $this->assertCount(2, $userPersisted->roles());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof RoleGranted);
    }

    public function testOwnerCanGrantRoleForUserOfSameAccount()
    {
        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $account = AccountMother::random();

        $owner = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $account->id());
        $user = UserMother::fromUsernameAndRoleAndAccount('user', Role::User, $account->id());

        $repository->add($user);

        $request = new GrantRoleRequest(
            userId: $user->id(),
            role: Role::Owner
        );

        $result = $sut($request, $owner);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof GrantRoleResponse);
        $this->assertTrue($user->id()->equals($result->userId));
        $this->assertCount(2, $result->roles);

        $userPersisted = $repository->find($result->userId);

        $this->assertNotNull($userPersisted);
        $this->assertTrue($userPersisted instanceof User);
        $this->assertCount(2, $userPersisted->roles());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof RoleGranted);
    }

    public function testOwnerCannotGrantRoleAdmin()
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
            role: Role::Admin
        );

        $sut($request, $owner);
    }

    public function testUnauthorizedAccess()
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $performBy = UserMother::ownerUser();

        $user = UserMother::registeredUser();

        $repository->add($user);

        $request = new GrantRoleRequest(
            userId: $user->id(),
            role: Role::Admin
        );

        $result = $sut($request, $performBy);
    }

    public function testIsHandle()
    {
        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $user = UserMother::random();

        $result = $sut->isHandled(new GrantRoleRequest(
            userId: $user->id(),
            role: Role::Admin
        ));

        $this->assertTrue($result);
    }

    private function createSUT($repository)
    {
        $authorizer = UserAuthorizerMother::create();

        return new GrantRoleService($repository, $authorizer);
    }
}
