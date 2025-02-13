<?php

namespace App\Tests\Unit\Security\Application\GrantRole;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\RoleGranted;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Security\Application\GrantRole\GrantRoleRequest;
use App\Security\Application\GrantRole\GrantRoleService;
use App\Security\Application\GrantRole\GrantRoleResponse;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Security\Infrastructure\Repository\User\InMemoryUserRepository;

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

        $user = UserMother::registeredUser();

        $repository->add($user);

        $request = new GrantRoleRequest(
            userId: $user->id(),
            role: Role::Admin
        );

        $result = $sut($request);

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

    private function createSUT($repository)
    {
        return new GrantRoleService($repository);
    }
}