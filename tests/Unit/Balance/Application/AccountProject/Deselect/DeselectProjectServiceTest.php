<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\AccountProject\Deselect;

use App\Balance\Application\AccountProject\AccountProjectAuthorizer;
use App\Balance\Application\AccountProject\Deselect\DeselectProjectRequest;
use App\Balance\Application\AccountProject\Deselect\DeselectProjectService;
use App\Balance\Application\CreditPurchase\Balance\CreditAvailability;
use App\Balance\Domain\Model\AccountProject\AccountProjectDeselected;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\AccountProject\ProjectHasAvailableCreditsException;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Infrastructure\Persistence\InMemory\Repository\InMemoryCreditPurchaseRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectMother;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\ProjectContribution\ProjectContributionRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class DeselectProjectServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = AccountProjectRepositoryMother::inMemory();

        $accountId = AccountId::generate();
        $projectId = ProjectMother::random()->id();

        $accountProject = AccountProjectMother::from($accountId, $projectId);
        $repository->add($accountProject);

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $result = $sut(new DeselectProjectRequest($accountId, $projectId), $user);

        $this->assertTrue($accountId->equals($result->accountId));
        $this->assertTrue($projectId->equals($result->projectId));

        $this->assertNull($repository->find($accountProject->id()));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(AccountProjectDeselected::class, $lastEvent);
    }

    public function testThrowsWhenNotFound(): void
    {
        $repository = AccountProjectRepositoryMother::inMemory();

        $accountId = AccountId::generate();

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new DeselectProjectRequest($accountId, ProjectMother::random()->id()), $user);
    }

    public function testThrowsWhenUserDoesNotBelongToAccount(): void
    {
        $repository = AccountProjectRepositoryMother::inMemory();

        $accountId = AccountId::generate();
        $projectId = ProjectMother::random()->id();

        $repository->add(AccountProjectMother::from($accountId, $projectId));

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new DeselectProjectRequest($accountId, $projectId), $user);
    }

    public function testThrowsWhenAccountHasAvailableCredits(): void
    {
        $repository = AccountProjectRepositoryMother::inMemory();

        $accountId = AccountId::generate();
        $projectId = ProjectMother::random()->id();

        $repository->add(AccountProjectMother::from($accountId, $projectId));

        $creditPurchaseRepository = new InMemoryCreditPurchaseRepository();
        $creditPurchaseRepository->add(CreditPurchaseMother::validated($accountId, $projectId));

        $sut = $this->createSUT($repository, $creditPurchaseRepository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $this->expectException(ProjectHasAvailableCreditsException::class);

        $sut(new DeselectProjectRequest($accountId, $projectId), $user);
    }

    private function createSUT(
        AccountProjectRepository $repository,
        ?CreditPurchaseRepository $creditPurchaseRepository = null,
    ): DeselectProjectService {
        $projectContributionRepository = ProjectContributionRepositoryMother::inMemory();

        return new DeselectProjectService(
            $repository,
            new CreditAvailability(
                $creditPurchaseRepository ?? new InMemoryCreditPurchaseRepository(),
                $projectContributionRepository,
            ),
            $projectContributionRepository,
            new AccountProjectAuthorizer(),
        );
    }
}
