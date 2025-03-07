<?php

namespace App\Tests\Unit\Security\Application\User\Remove;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\UserRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Security\Application\User\Remove\RemoveUserRequest;
use App\Security\Application\User\Remove\RemoveUserService;
use App\Security\Application\User\Remove\RemoveUserResponse;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Security\Infrastructure\Repository\User\InMemoryUserRepository;

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

        $repository->add($user);

        $request = new RemoveUserRequest(
            userId: $user->id()
        );

        $result = $sut($request);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RemoveUserResponse);
        $this->assertTrue($user->id()->equals($result->userId));
        
        $userPersisted = $repository->find($result->userId);

        $this->assertNull($userPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof UserRemoved);
    }

    private function createSUT($repository)
    {
        return new RemoveUserService($repository);
    }
}