<?php

namespace App\Tests\Unit\Security\Application\User\List;

use App\Security\Application\UnauthorizedActionException;
use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Security\Application\User\List\ListUsersRequest;
use App\Security\Application\User\List\ListUsersService;
use App\Security\Application\User\List\ListUsersResponse;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Security\Infrastructure\Repository\User\InMemoryUserRepository;
use App\Tests\Unit\Security\Application\User\UserAuthorizerMother;

class ListUsersServiceTest extends TestCase
{
    protected function setUp(): void
    {
        
    }

    public function testInvoke()
    {
        $repository = new InMemoryUserRepository();

        $performBy = UserMother::adminUser();

        $sut = $this->createSUT($repository);

        $repository->add(UserMother::random());
        $repository->add(UserMother::random());
        $repository->add(UserMother::random());
        $repository->add(UserMother::random());

        $request = new ListUsersRequest();

        $result = $sut($request, $performBy);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListUsersResponse);
        $this->assertCount(4, $result->users);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testAccessByOwner()
    {
        $repository = new InMemoryUserRepository();

        $account = AccountMother::random();

        $performBy = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $account->id());

        $sut = $this->createSUT($repository);

        $repository->add(UserMother::fromUsernameAndAccount('user1', $account));
        $repository->add(UserMother::fromUsernameAndAccount('user2', $account));
        $repository->add(UserMother::fromUsernameAndAccount('user3', $account));
        $repository->add(UserMother::fromUsernameAndAccount('user4', $account));

        $request = new ListUsersRequest(
            account: $account
        );

        $result = $sut($request, $performBy);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListUsersResponse);
        $this->assertCount(4, $result->users);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testUnauthorizedAccess()
    {
        $this->expectException(UnauthorizedActionException::class);
        
        $repository = new InMemoryUserRepository();

        $account = AccountMother::random();
        $otherAccount = AccountMother::random();

        $performBy = UserMother::fromUsernameAndRoleAndAccount('user', Role::Owner, $otherAccount->id());

        $sut = $this->createSUT($repository);

        $repository->add(UserMother::fromUsernameAndAccount('user1', $account));
        $repository->add(UserMother::fromUsernameAndAccount('user2', $account));
        $repository->add(UserMother::fromUsernameAndAccount('user3', $account));
        $repository->add(UserMother::fromUsernameAndAccount('user4', $account));

        $request = new ListUsersRequest(
            account: $account
        );

        $sut($request, $performBy);
    }

    private function createSUT($repository)
    {
        return new ListUsersService($repository, UserAuthorizerMother::create());
    }
}