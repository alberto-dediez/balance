<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\AccountProject;

use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class AccountProjectRepositoryTest extends KernelTestCase
{
    public function testAddAccountProjectToRepository(): void
    {
        $repository = $this->createSUT();

        $accountProject = AccountProjectMother::random();

        $this->assertNull($repository->find($accountProject->id()));

        $repository->add($accountProject);

        $recovered = $repository->find($accountProject->id());

        $this->assertNotNull($recovered);
        $this->assertTrue($accountProject->id()->equals($recovered->id()));
    }

    public function testRemoveAccountProjectFromRepository(): void
    {
        $repository = $this->createSUT();

        $accountProject = AccountProjectMother::random();

        $repository->add($accountProject);

        $this->assertNotNull($repository->find($accountProject->id()));

        $repository->remove($accountProject);

        $this->assertNull($repository->find($accountProject->id()));
    }

    public function testFindByFilterByAccountId(): void
    {
        $repository = $this->createSUT();

        $accountId = AccountId::generate();

        $repository->add(AccountProjectMother::from($accountId, ProjectId::generate()));
        $repository->add(AccountProjectMother::from($accountId, ProjectId::generate()));
        $repository->add(AccountProjectMother::random());

        $result = $repository->findByFilter(new AccountProjectFilter(accountId: $accountId, limit: 0));

        $this->assertCount(2, $result);
    }

    public function testFindByFilterByProjectId(): void
    {
        $repository = $this->createSUT();

        $projectId = ProjectId::generate();

        $repository->add(AccountProjectMother::from(AccountId::generate(), $projectId));
        $repository->add(AccountProjectMother::random());

        $result = $repository->findByFilter(new AccountProjectFilter(projectId: $projectId, limit: 0));

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(AccountProjectMother::random());
        $repository->add(AccountProjectMother::random());
        $repository->add(AccountProjectMother::random());

        $result = $repository->findByFilter(new AccountProjectFilter(limit: 0));

        $this->assertCount(3, $result);
    }

    abstract protected function createSUT(): AccountProjectRepository;
}
