<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\CreditPurchase\Allocations;

use App\Balance\Application\CreditPurchase\Allocations\ListCreditPurchaseAllocationsRequest;
use App\Balance\Application\CreditPurchase\Allocations\ListCreditPurchaseAllocationsService;
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

class ListCreditPurchaseAllocationsServiceTest extends TestCase
{
    public function testReturnsAllocationsAndAvailableTonnes(): void
    {
        $accountId = AccountId::generate();
        $projectId = ProjectId::generate();

        $creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();
        $creditPurchase = CreditPurchaseMother::validated($accountId, $projectId, tonnes: 10.0);
        $creditPurchaseRepository->add($creditPurchase);

        $projectContributionRepository = ProjectContributionRepositoryMother::inMemory();

        $firstContribution = ProjectContributionMother::paid($accountId, $projectId, tonnes: 3.0);
        $firstContribution->allocateCredits([
            new AllocatedCreditLine(
                position: 0,
                creditPurchaseId: $creditPurchase->id(),
                purchaseReference: $creditPurchase->reference(),
                tonnes: 3.0,
                startTonnes: 0.0,
            ),
        ]);
        $projectContributionRepository->add($firstContribution);

        $secondContribution = ProjectContributionMother::paid($accountId, $projectId, tonnes: 2.0);
        $secondContribution->allocateCredits([
            new AllocatedCreditLine(
                position: 0,
                creditPurchaseId: $creditPurchase->id(),
                purchaseReference: $creditPurchase->reference(),
                tonnes: 2.0,
                startTonnes: 3.0,
            ),
        ]);
        $projectContributionRepository->add($secondContribution);

        $sut = new ListCreditPurchaseAllocationsService(
            $creditPurchaseRepository,
            $projectContributionRepository,
            new CreditPurchaseAuthorizer(),
        );

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $result = $sut(new ListCreditPurchaseAllocationsRequest($creditPurchase->id()), $user);

        $this->assertCount(2, $result->allocations);
        $this->assertSame(0.0, $result->allocations[0]->startTonnes());
        $this->assertSame(3.0, $result->allocations[1]->startTonnes());
        $this->assertSame(5.0, $result->allocatedTonnes);
        $this->assertSame(5.0, $result->availableTonnes);
    }

    public function testThrowsWhenOwnerRequestsAnotherAccountPurchase(): void
    {
        $creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();
        $creditPurchase = CreditPurchaseMother::validated();
        $creditPurchaseRepository->add($creditPurchase);

        $sut = new ListCreditPurchaseAllocationsService(
            $creditPurchaseRepository,
            ProjectContributionRepositoryMother::inMemory(),
            new CreditPurchaseAuthorizer(),
        );

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new ListCreditPurchaseAllocationsRequest($creditPurchase->id()), $user);
    }
}
