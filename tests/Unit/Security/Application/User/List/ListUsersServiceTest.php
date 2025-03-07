<?php

namespace App\Tests\Unit\Security\Application\User\List;

use PHPUnit\Framework\TestCase;
use App\Security\Application\User\List\ListUsersRequest;
use App\Security\Application\User\List\ListUsersService;
use App\Security\Application\User\List\ListUsersResponse;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
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