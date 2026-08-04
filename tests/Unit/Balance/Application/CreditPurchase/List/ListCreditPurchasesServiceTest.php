<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\CreditPurchase\List;

use App\Balance\Application\CreditPurchase\CreditPurchaseAuthorizer;
use App\Balance\Application\CreditPurchase\List\ListCreditPurchasesRequest;
use App\Balance\Application\CreditPurchase\List\ListCreditPurchasesService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListCreditPurchasesServiceTest extends TestCase
{
    public function testAdminListsAllPurchases(): void
    {
        $repository = CreditPurchaseRepositoryMother::inMemory();
        $repository->add(CreditPurchaseMother::random());
        $repository->add(CreditPurchaseMother::random());

        $sut = new ListCreditPurchasesService($repository, new CreditPurchaseAuthorizer());

        $result = $sut(new ListCreditPurchasesRequest(limit: 0), UserMother::adminUser());

        $this->assertSame(2, $result->totalResults);
    }

    public function testOwnerListsOwnAccountPurchasesOnly(): void
    {
        $repository = CreditPurchaseRepositoryMother::inMemory();

        $accountId = AccountId::generate();
        $repository->add(CreditPurchaseMother::pending($accountId));
        $repository->add(CreditPurchaseMother::random());

        $sut = new ListCreditPurchasesService($repository, new CreditPurchaseAuthorizer());

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $result = $sut(new ListCreditPurchasesRequest(accountId: $accountId, limit: 0), $user);

        $this->assertSame(1, $result->totalResults);
    }

    public function testThrowsWhenOwnerRequestsAnotherAccount(): void
    {
        $repository = CreditPurchaseRepositoryMother::inMemory();

        $sut = new ListCreditPurchasesService($repository, new CreditPurchaseAuthorizer());

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new ListCreditPurchasesRequest(accountId: AccountId::generate(), limit: 0), $user);
    }
}
