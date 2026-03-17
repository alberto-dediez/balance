<?php

namespace App\Tests\Unit\Security\Application\User\List;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\List\ListUsersRequest;
use App\Security\Application\User\List\ListUsersResponse;
use App\Security\Application\User\List\ListUsersService;
use App\Security\Domain\Model\User\Role;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Tests\Unit\Security\Application\User\UserAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListUsersServiceTest extends TestCase
{
    protected function setUp(): void
    {

    }

    public function testInvoke(): void
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

        $this->assertCount(4, $result->users);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testAccessByOwner(): void
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

        $this->assertCount(4, $result->users);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testUnauthorizedAccess(): void
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

    private function createSUT(InMemoryUserRepository $repository): ListUsersService
    {
        return new ListUsersService($repository, UserAuthorizerMother::create());
    }
}
