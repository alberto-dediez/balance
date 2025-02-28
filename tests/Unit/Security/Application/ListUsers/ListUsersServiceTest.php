<?php

namespace App\Tests\Unit\Security\Application\ListUsers;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Security\Domain\Model\User\RoleGranted;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Security\Application\ListUsers\ListUsersRequest;
use App\Security\Application\ListUsers\ListUsersService;
use App\Security\Application\ListUsers\ListUsersResponse;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Security\Infrastructure\Repository\User\InMemoryUserRepository;

class ListUsersServiceTest extends TestCase
{
    protected function setUp(): void
    {
        
    }

    public function testInvoke()
    {
        $repository = new InMemoryUserRepository();

        $sut = $this->createSUT($repository);

        $repository->add(UserMother::random());
        $repository->add(UserMother::random());
        $repository->add(UserMother::random());
        $repository->add(UserMother::random());

        $request = new ListUsersRequest();

        $result = $sut($request);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListUsersResponse);
        $this->assertCount(4, $result->users);
        $this->assertEquals(4, $result->totalResults);
    }

    private function createSUT($repository)
    {
        return new ListUsersService($repository);
    }
}