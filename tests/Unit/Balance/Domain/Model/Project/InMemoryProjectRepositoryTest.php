<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\Project;

use App\Balance\Domain\Model\Project\ProjectFilter;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectMother;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectRepositoryMother;

class InMemoryProjectRepositoryTest extends ProjectRepositoryTest
{
    protected function createSUT(Locale $locale = Locale::Spanish): ProjectRepository
    {
        return ProjectRepositoryMother::inMemory($locale);
    }

    public function testFindByFilterWithSelection(): void
    {
        $accountProjectRepository = AccountProjectRepositoryMother::inMemory();

        $repository = ProjectRepositoryMother::inMemory(accountProjectRepository: $accountProjectRepository);

        $accountId = AccountId::generate();

        $selectedProject = ProjectMother::fromName('Proyecto adherido');
        $unselectedProject = ProjectMother::fromName('Proyecto no adherido');

        $repository->add($selectedProject);
        $repository->add($unselectedProject);

        $accountProjectRepository->add(AccountProjectMother::from($accountId, $selectedProject->id()));

        $allProjects = $repository->findByFilter(new ProjectFilter(selectedByAccountId: $accountId));

        $this->assertCount(2, $allProjects);

        $selectedProjects = $repository->findByFilter(new ProjectFilter(selected: true, selectedByAccountId: $accountId));

        $this->assertCount(1, $selectedProjects);
        $this->assertEquals($selectedProject->id(), $selectedProjects[0]->id());

        $unselectedProjects = $repository->findByFilter(new ProjectFilter(selected: false, selectedByAccountId: $accountId));

        $this->assertCount(1, $unselectedProjects);
        $this->assertEquals($unselectedProject->id(), $unselectedProjects[0]->id());
    }

    public function testFindByFilterWithSelectionOfAnotherAccount(): void
    {
        $accountProjectRepository = AccountProjectRepositoryMother::inMemory();

        $repository = ProjectRepositoryMother::inMemory(accountProjectRepository: $accountProjectRepository);

        $project = ProjectMother::fromName('Proyecto adherido');

        $repository->add($project);

        $accountProjectRepository->add(AccountProjectMother::from(AccountId::generate(), $project->id()));

        $selectedProjects = $repository->findByFilter(new ProjectFilter(selected: true, selectedByAccountId: AccountId::generate()));

        $this->assertCount(0, $selectedProjects);
    }
}
