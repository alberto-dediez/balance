<?php

namespace App\Tests\Unit\Security\Application\User\Remove;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\Remove\RemoveUserRequest;
use App\Security\Application\User\Remove\RemoveUserResponse;
use App\Security\Application\User\Remove\RemoveUserService;
use App\Security\Domain\Model\User\UserRemoved;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Application\User\UserAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveUserServiceTest extends TestCase
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

        $performBy = UserMother::adminUser();

        $repository->add($user);

        $request = new RemoveUserRequest(
            userId: $user->id()
        );

        $result = $sut($request, $performBy);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RemoveUserResponse);
        $this->assertTrue($user->id()->equals($result->userId));

        $userPersisted = $repository->find($result->userId);

        $this->assertNull($userPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof UserRemoved);
    }

    public function testUnauthorizedAccess()
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $user = UserMother::registeredUser();

        $performBy = UserMother::ownerUser();

        $repository->add($user);

        $request = new RemoveUserRequest(
            userId: $user->id()
        );

        $sut($request, $performBy);
    }

    public function testIsHandle()
    {
        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $user = UserMother::random();

        $result = $sut->isHandled(new RemoveUserRequest(
            userId: $user->id()
        ));

        $this->assertTrue($result);
    }

    private function createSUT($repository)
    {
        return new RemoveUserService($repository, UserAuthorizerMother::create());
    }
}
