<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\AccountProject\Select;

use App\Balance\Application\AccountProject\AccountProjectAuthorizer;
use App\Balance\Application\AccountProject\Select\SelectProjectRequest;
use App\Balance\Application\AccountProject\Select\SelectProjectService;
use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\AccountProject\AccountProjectSelected;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectRepositoryMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class SelectProjectServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $accountProjectRepository = AccountProjectRepositoryMother::inMemory();
        $projectRepository = ProjectRepositoryMother::inMemory();

        $project = ProjectMother::registeredProject();
        $projectRepository->add($project);

        $accountId = AccountId::generate();

        $sut = $this->createSUT($accountProjectRepository, $projectRepository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $result = $sut(new SelectProjectRequest($accountId, $project->id()), $user);

        $this->assertTrue($accountId->equals($result->accountId));
        $this->assertTrue($project->id()->equals($result->projectId));

        $persisted = $accountProjectRepository->findByFilter(
            new AccountProjectFilter(accountId: $accountId, projectId: $project->id(), limit: 1),
        );

        $this->assertCount(1, $persisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertInstanceOf(AccountProjectSelected::class, $lastEvent);
    }

    public function testIsIdempotent(): void
    {
        $accountProjectRepository = AccountProjectRepositoryMother::inMemory();
        $projectRepository = ProjectRepositoryMother::inMemory();

        $project = ProjectMother::registeredProject();
        $projectRepository->add($project);

        $accountId = AccountId::generate();

        $sut = $this->createSUT($accountProjectRepository, $projectRepository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $first = $sut(new SelectProjectRequest($accountId, $project->id()), $user);
        $second = $sut(new SelectProjectRequest($accountId, $project->id()), $user);

        $this->assertTrue($first->accountProjectId->equals($second->accountProjectId));

        $persisted = $accountProjectRepository->findByFilter(
            new AccountProjectFilter(accountId: $accountId, projectId: $project->id(), limit: 0),
        );

        $this->assertCount(1, $persisted);
    }

    public function testThrowsWhenProjectDoesNotExist(): void
    {
        $accountProjectRepository = AccountProjectRepositoryMother::inMemory();
        $projectRepository = ProjectRepositoryMother::inMemory();

        $accountId = AccountId::generate();

        $sut = $this->createSUT($accountProjectRepository, $projectRepository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new SelectProjectRequest($accountId, ProjectMother::random()->id()), $user);
    }

    public function testThrowsWhenUserDoesNotBelongToAccount(): void
    {
        $accountProjectRepository = AccountProjectRepositoryMother::inMemory();
        $projectRepository = ProjectRepositoryMother::inMemory();

        $project = ProjectMother::registeredProject();
        $projectRepository->add($project);

        $sut = $this->createSUT($accountProjectRepository, $projectRepository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, AccountId::generate());

        $this->expectException(UnauthorizedActionException::class);

        $sut(new SelectProjectRequest(AccountId::generate(), $project->id()), $user);
    }

    private function createSUT(
        AccountProjectRepository $accountProjectRepository,
        ProjectRepository $projectRepository,
    ): SelectProjectService {
        return new SelectProjectService(
            accountProjectRepository: $accountProjectRepository,
            projectRepository: $projectRepository,
            authorizer: new AccountProjectAuthorizer(),
        );
    }
}
