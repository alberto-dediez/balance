<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\CreditPurchase\Balance;

use App\Balance\Application\CreditPurchase\Balance\CreditAvailability;
use App\Balance\Application\CreditPurchase\Balance\ListAccountCreditsRequest;
use App\Balance\Application\CreditPurchase\Balance\ListAccountCreditsService;
use App\Balance\Application\CreditPurchase\CreditPurchaseAuthorizer;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\ProjectContribution\AllocatedCreditLine;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
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

        $projectContributionRepository = ProjectContributionRepositoryMother::inMemory();

        $sut = new ListAccountCreditsService(
            new CreditAvailability($repository, $projectContributionRepository),
            $repository,
            $projectContributionRepository,
            new CreditPurchaseAuthorizer(),
        );

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $result = $sut(new ListAccountCreditsRequest($accountId), $user);

        $this->assertCount(1, $result->balances);
        $this->assertSame(5.0, $result->balances[0]->purchasedTonnes());
    }

    public function testGroupsPurchasesAndAllocationsByProject(): void
    {
        $accountId = AccountId::generate();
        $projectId = ProjectId::generate();
        $pendingProjectId = ProjectId::generate();

        $repository = CreditPurchaseRepositoryMother::inMemory();
        $creditPurchase = CreditPurchaseMother::validated($accountId, $projectId, tonnes: 10.0);
        $repository->add($creditPurchase);
        $repository->add(CreditPurchaseMother::pending($accountId, $pendingProjectId, tonnes: 4.0));

        $projectContributionRepository = ProjectContributionRepositoryMother::inMemory();
        $contribution = ProjectContributionMother::paid($accountId, $projectId, tonnes: 3.0);
        $contribution->allocateCredits([
            new AllocatedCreditLine(
                position: 0,
                creditPurchaseId: $creditPurchase->id(),
                purchaseReference: $creditPurchase->reference(),
                tonnes: 3.0,
                startTonnes: 0.0,
            ),
        ]);
        $projectContributionRepository->add($contribution);

        $sut = new ListAccountCreditsService(
            new CreditAvailability($repository, $projectContributionRepository),
            $repository,
            $projectContributionRepository,
            new CreditPurchaseAuthorizer(),
        );

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $result = $sut(new ListAccountCreditsRequest($accountId), $user);

        $this->assertCount(2, $result->balances);
        $this->assertCount(1, $result->purchasesByProject[(string) $projectId]);
        $this->assertCount(1, $result->purchasesByProject[(string) $pendingProjectId]);
        $this->assertCount(1, $result->allocationsByPurchase[(string) $creditPurchase->id()]);
        $this->assertSame(3.0, $result->allocationsByPurchase[(string) $creditPurchase->id()][0]->tonnes());
    }

    public function testThrowsWhenOwnerRequestsAnotherAccount(): void
    {
        $repository = CreditPurchaseRepositoryMother::inMemory();

        $projectContributionRepository = ProjectContributionRepositoryMother::inMemory();

        $sut = new ListAccountCreditsService(
            new CreditAvailability($repository, $projectContributionRepository),
            $repository,
            $projectContributionRepository,
            new CreditPurchaseAuthorizer(),
        );

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new ListAccountCreditsRequest(AccountId::generate()), $user);
    }
}
