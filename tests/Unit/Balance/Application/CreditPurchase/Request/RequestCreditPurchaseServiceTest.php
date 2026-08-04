<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\CreditPurchase\Request;

use App\Balance\Application\AccountProject\AccountProjectAuthorizer;
use App\Balance\Application\AccountProject\Select\SelectProjectService;
use App\Balance\Application\CreditPurchase\CreditPurchaseAuthorizer;
use App\Balance\Application\CreditPurchase\Request\RequestCreditPurchaseRequest;
use App\Balance\Application\CreditPurchase\Request\RequestCreditPurchaseService;
use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepository;
use App\Balance\Domain\Model\CreditPurchase\CreditPurchaseRequested;
use App\Balance\Domain\Model\CreditPurchase\ProjectNotCompensableException;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\Project\ProjectStatus;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\CreditPurchase\CreditPurchaseRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RequestCreditPurchaseServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvokeRequestsCreditsAndSelectsProject(): void
    {
        $creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();
        $projectRepository = ProjectRepositoryMother::inMemory();
        $accountProjectRepository = AccountProjectRepositoryMother::inMemory();

        $project = ProjectMother::random();
        $projectRepository->add($project);

        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $sut = $this->createSUT($creditPurchaseRepository, $projectRepository, $accountProjectRepository);

        $result = $sut(new RequestCreditPurchaseRequest($accountId, $project->id(), 10.0), $user);

        $creditPurchase = $creditPurchaseRepository->find($result->creditPurchaseId);

        $this->assertNotNull($creditPurchase);
        $this->assertSame(10.0, $creditPurchase->requestedTonnes());

        $selected = $accountProjectRepository->findByFilter(
            new AccountProjectFilter(accountId: $accountId, projectId: $project->id(), limit: 1),
        );

        $this->assertCount(1, $selected);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(CreditPurchaseRequested::class, $lastEvent);
    }

    public function testInvokeDoesNotDuplicateSelectionWhenAlreadyAdhered(): void
    {
        $creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();
        $projectRepository = ProjectRepositoryMother::inMemory();
        $accountProjectRepository = AccountProjectRepositoryMother::inMemory();

        $project = ProjectMother::random();
        $projectRepository->add($project);

        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $sut = $this->createSUT($creditPurchaseRepository, $projectRepository, $accountProjectRepository);

        $sut(new RequestCreditPurchaseRequest($accountId, $project->id(), 5.0), $user);
        $sut(new RequestCreditPurchaseRequest($accountId, $project->id(), 3.0), $user);

        $selected = $accountProjectRepository->findByFilter(
            new AccountProjectFilter(accountId: $accountId, projectId: $project->id(), limit: 0),
        );

        $this->assertCount(1, $selected);
    }

    public function testThrowsWhenProjectIsNotCompensable(): void
    {
        $creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();
        $projectRepository = ProjectRepositoryMother::inMemory();
        $accountProjectRepository = AccountProjectRepositoryMother::inMemory();

        $project = ProjectMother::from(
            name: 'social-project',
            id: (string) ProjectId::generate(),
            projectType: ProjectType::SocialProject,
            projectStatus: ProjectStatus::Pending,
        );
        $projectRepository->add($project);

        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $sut = $this->createSUT($creditPurchaseRepository, $projectRepository, $accountProjectRepository);

        $this->expectException(ProjectNotCompensableException::class);

        $sut(new RequestCreditPurchaseRequest($accountId, $project->id(), 10.0), $user);
    }

    public function testThrowsWhenProjectDoesNotExist(): void
    {
        $creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();
        $projectRepository = ProjectRepositoryMother::inMemory();
        $accountProjectRepository = AccountProjectRepositoryMother::inMemory();

        $accountId = AccountId::generate();
        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $sut = $this->createSUT($creditPurchaseRepository, $projectRepository, $accountProjectRepository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new RequestCreditPurchaseRequest($accountId, ProjectMother::random()->id(), 10.0), $user);
    }

    public function testThrowsWhenUserDoesNotBelongToAccount(): void
    {
        $creditPurchaseRepository = CreditPurchaseRepositoryMother::inMemory();
        $projectRepository = ProjectRepositoryMother::inMemory();
        $accountProjectRepository = AccountProjectRepositoryMother::inMemory();

        $project = ProjectMother::random();
        $projectRepository->add($project);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $sut = $this->createSUT($creditPurchaseRepository, $projectRepository, $accountProjectRepository);

        $this->expectException(UnauthorizedActionException::class);

        $sut(new RequestCreditPurchaseRequest(AccountId::generate(), $project->id(), 10.0), $user);
    }

    private function createSUT(
        CreditPurchaseRepository $creditPurchaseRepository,
        ProjectRepository $projectRepository,
        AccountProjectRepository $accountProjectRepository,
    ): RequestCreditPurchaseService {
        $selectProjectService = new SelectProjectService(
            accountProjectRepository: $accountProjectRepository,
            projectRepository: $projectRepository,
            authorizer: new AccountProjectAuthorizer(),
        );

        return new RequestCreditPurchaseService(
            creditPurchaseRepository: $creditPurchaseRepository,
            projectRepository: $projectRepository,
            accountProjectRepository: $accountProjectRepository,
            selectProjectService: $selectProjectService,
            authorizer: new CreditPurchaseAuthorizer(),
        );
    }
}
