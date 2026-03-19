<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Account\List;

use App\Security\Application\Account\List\ListAccountsRequest;
use App\Security\Application\Account\List\ListAccountsService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryAccountRepository;
use App\Tests\Unit\Security\Application\Account\AccountAuthorizerMother;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListAccountsServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
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

        $this->assertCount(4, $result->accounts);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testUnauthorizedAccess(): void
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

    private function createSUT(InMemoryAccountRepository $repository): ListAccountsService
    {
        $authorizer = AccountAuthorizerMother::create();

        return new ListAccountsService($repository, $authorizer);
    }
}
