<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\AccountProject\List;

use App\Balance\Application\AccountProject\AccountProjectAuthorizer;
use App\Balance\Application\AccountProject\List\ListAccountProjectsRequest;
use App\Balance\Application\AccountProject\List\ListAccountProjectsService;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectMother;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListAccountProjectsServiceTest extends TestCase
{
    public function testInvoke(): void
    {
        $repository = AccountProjectRepositoryMother::inMemory();

        $accountId = AccountId::generate();

        $repository->add(AccountProjectMother::from($accountId, ProjectId::generate()));
        $repository->add(AccountProjectMother::from($accountId, ProjectId::generate()));
        $repository->add(AccountProjectMother::random());

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);

        $result = $sut(new ListAccountProjectsRequest(accountId: $accountId, limit: 0), $user);

        $this->assertCount(2, $result->accountProjects);
        $this->assertEquals(2, $result->totalResults);
    }

    public function testAdminCanListByProjectId(): void
    {
        $repository = AccountProjectRepositoryMother::inMemory();

        $projectId = ProjectId::generate();

        $repository->add(AccountProjectMother::from(AccountId::generate(), $projectId));
        $repository->add(AccountProjectMother::random());

        $sut = $this->createSUT($repository);

        $result = $sut(new ListAccountProjectsRequest(projectId: $projectId, limit: 0), UserMother::adminUser());

        $this->assertCount(1, $result->accountProjects);
    }

    private function createSUT(AccountProjectRepository $repository): ListAccountProjectsService
    {
        return new ListAccountProjectsService($repository, new AccountProjectAuthorizer());
    }
}
