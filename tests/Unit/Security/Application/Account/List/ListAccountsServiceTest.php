<?php

namespace App\Tests\Unit\Security\Application\Account\List;

use PHPUnit\Framework\TestCase;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Security\Application\Account\List\ListAccountsRequest;
use App\Security\Application\Account\List\ListAccountsService;
use App\Security\Application\Account\List\ListAccountsResponse;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Security\Infrastructure\Repository\Account\InMemoryAccountRepository;
use App\Tests\Unit\Security\Application\Account\AccountAuthorizerMother;

class ListAccountsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        
    }

    public function testInvoke()
    {
        $repository = new InMemoryAccountRepository();

        $user = UserMother::adminUser();

        $sut = $this->createSUT($repository);

        $repository->add(AccountMother::random());
        $repository->add(AccountMother::random());
        $repository->add(AccountMother::random());
        $repository->add(AccountMother::random());

        $request = new ListAccountsRequest();

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListAccountsResponse);
        $this->assertCount(4, $result->accounts);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testUnauthorizedAccess()
    {
        $this->expectException(UnauthorizedActionException::class);

        $repository = new InMemoryAccountRepository();

        $user = UserMother::ownerUser();

        $sut = $this->createSUT($repository);

        $repository->add(AccountMother::random());
        $repository->add(AccountMother::random());
        $repository->add(AccountMother::random());
        $repository->add(AccountMother::random());

        $request = new ListAccountsRequest();

        $sut($request, $user);
    }

    private function createSUT($repository)
    {
        $authorizer = AccountAuthorizerMother::create();

        return new ListAccountsService($repository, $authorizer);
    }
}