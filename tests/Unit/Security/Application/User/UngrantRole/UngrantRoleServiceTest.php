<?php

namespace App\Tests\Unit\Security\Application\User\UngrantRole;

use App\Security\Application\UnauthorizedActionException;
use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\RoleUngranted;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Security\Application\User\UngrantRole\UngrantRoleRequest;
use App\Security\Application\User\UngrantRole\UngrantRoleService;
use App\Security\Application\User\UngrantRole\UngrantRoleResponse;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Security\Infrastructure\Repository\User\InMemoryUserRepository;
use App\Tests\Unit\Security\Application\User\UserAuthorizerMother;

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

    private function createSUT($repository)
    {
        return new UngrantRoleService($repository, UserAuthorizerMother::create());
    }
}