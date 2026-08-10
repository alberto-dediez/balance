<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\ProjectContribution\List;

use App\Balance\Application\ProjectContribution\List\ListProjectContributionsRequest;
use App\Balance\Application\ProjectContribution\List\ListProjectContributionsService;
use App\Balance\Application\ProjectContribution\ProjectContributionAuthorizer;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionRepository;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionStatus;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListProjectContributionsServiceTest extends TestCase
{
    public function testAdminListsAllContributions(): void
    {
        $repository = ProjectContributionRepositoryMother::inMemory();
        $repository->add(ProjectContributionMother::random());
        $repository->add(ProjectContributionMother::random());

        $result = $this->createSUT($repository)(
            new ListProjectContributionsRequest(limit: 0),
            UserMother::adminUser(),
        );

        $this->assertSame(2, $result->totalResults);
        $this->assertCount(2, $result->projectContributions);
    }

    public function testTheStatusFilterIsApplied(): void
    {
        $repository = ProjectContributionRepositoryMother::inMemory();
        $repository->add(ProjectContributionMother::paid());
        $repository->add(ProjectContributionMother::pending());

        $result = $this->createSUT($repository)(
            new ListProjectContributionsRequest(status: ProjectContributionStatus::Paid, limit: 0),
            UserMother::adminUser(),
        );

        $this->assertSame(1, $result->totalResults);
    }

    public function testOwnerListsOwnAccountContributionsOnly(): void
    {
        $accountId = AccountId::generate();

        $repository = ProjectContributionRepositoryMother::inMemory();
        $repository->add(ProjectContributionMother::pending($accountId));
        $repository->add(ProjectContributionMother::random());

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $result = $this->createSUT($repository)(
            new ListProjectContributionsRequest(accountId: $accountId, limit: 0),
            $user,
        );

        $this->assertSame(1, $result->totalResults);
    }

    public function testThrowsWhenOwnerRequestsAnotherAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()(new ListProjectContributionsRequest(accountId: AccountId::generate(), limit: 0), $user);
    }

    public function testThrowsWhenOwnerRequestsEveryAccount(): void
    {
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $this->expectException(UnauthorizedActionException::class);

        $this->createSUT()(new ListProjectContributionsRequest(limit: 0), $user);
    }

    private function createSUT(?ProjectContributionRepository $repository = null): ListProjectContributionsService
    {
        return new ListProjectContributionsService(
            $repository ?? ProjectContributionRepositoryMother::inMemory(),
            new ProjectContributionAuthorizer(),
        );
    }
}
