<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\CreditPurchase\Balance;

use App\Balance\Application\CreditPurchase\Balance\CreditAvailability;
use App\Balance\Application\CreditPurchase\Balance\ListAccountCreditsRequest;
use App\Balance\Application\CreditPurchase\Balance\ListAccountCreditsService;
use App\Balance\Application\CreditPurchase\CreditPurchaseAuthorizer;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListAccountCreditsServiceTest extends TestCase
{
    public function testReturnsBalancesForAccount(): void
    {
        $repository = CreditPurchaseRepositoryMother::inMemory();

        $accountId = AccountId::generate();
        $repository->add(CreditPurchaseMother::validated($accountId, tonnes: 5.0));
        $repository->add(CreditPurchaseMother::random());

        $sut = new ListAccountCreditsService(
            new CreditAvailability($repository, ProjectContributionRepositoryMother::inMemory()),
            new CreditPurchaseAuthorizer(),
        );

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $result = $sut(new ListAccountCreditsRequest($accountId), $user);

        $this->assertCount(1, $result->balances);
        $this->assertSame(5.0, $result->balances[0]->purchasedTonnes());
    }

    public function testThrowsWhenOwnerRequestsAnotherAccount(): void
    {
        $repository = CreditPurchaseRepositoryMother::inMemory();

        $sut = new ListAccountCreditsService(
            new CreditAvailability($repository, ProjectContributionRepositoryMother::inMemory()),
            new CreditPurchaseAuthorizer(),
        );

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new ListAccountCreditsRequest(AccountId::generate()), $user);
    }
}
