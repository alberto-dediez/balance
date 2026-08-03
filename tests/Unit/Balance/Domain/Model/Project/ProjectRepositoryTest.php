<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Model\Project;

use App\Balance\Domain\Model\Project\ProjectFilter;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class ProjectRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    public function testAddProjectToRepository(): void
    {
        $repository = $this->createSUT();

        $project = ProjectMother::registeredProject();

        $this->assertNull($repository->find($project->id()));

        $repository->add($project);

        $recoveredProject = $repository->find($project->id());

        $this->assertNotNull($recoveredProject);
        $this->assertEquals($project->id(), $recoveredProject->id());
    }

    public function testRemoveProjectToRepository(): void
    {
        $repository = $this->createSUT();

        $project = ProjectMother::registeredProject();

        $repository->add($project);

        $recoveredProject = $repository->find($project->id());

        $this->assertNotNull($recoveredProject);
        $this->assertEquals($project->id(), $recoveredProject->id());

        $repository->remove($project);

        $this->assertNull($repository->find($project->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(ProjectMother::random());
        $repository->add(ProjectMother::random());
        $repository->add(ProjectMother::random());
        $repository->add(ProjectMother::random());
        $repository->add(ProjectMother::random());
        $repository->add(ProjectMother::fromName('Nombre específico'));

        $filter = new ProjectFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new ProjectFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(ProjectMother::random());
        $repository->add(ProjectMother::random());
        $repository->add(ProjectMother::random());
        $repository->add(ProjectMother::random());
        $repository->add(ProjectMother::random());
        $repository->add(ProjectMother::fromName('Nombre específico'));

        $filter = new ProjectFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $project = ProjectMother::registeredProject();

        $repository->add($project);

        $recoveredProject = $repository->find($project->id());

        $this->assertNotNull($recoveredProject);

        $recoveredProject->remove();

        $repository->add($project);

        $this->assertNull($repository->find($project->id()));
    }

    public function testFindRecoverWithTranslation(): void
    {
        $repository = $this->createSUT(Locale::English);

        $project = ProjectMother::registeredProject();

        $this->assertNull($repository->find($project->id()));

        $project->translate('translated-name', 'translated-description', Locale::English);

        $repository->add($project);

        $recoveredProject = $repository->find($project->id());

        $this->assertNotNull($recoveredProject);
        $this->assertEquals($project->id(), $recoveredProject->id());
        $this->assertEquals('translated-name', $recoveredProject->name());
        $this->assertEquals('translated-description', $recoveredProject->description());
    }

    public function testFindByFilterWithNameTranslated(): void
    {
        $repository = $this->createSUT(Locale::English);

        $project = ProjectMother::registeredProject();

        $project->translate('translated-name', 'translated-description', Locale::English);

        $repository->add($project);

        $recoveredProjects = $repository->findByFilter(new ProjectFilter(name: 'translated-name'));

        $this->assertCount(1, $recoveredProjects);
        $this->assertEquals($project->id(), $recoveredProjects[0]->id());
        $this->assertEquals('translated-name', $recoveredProjects[0]->name());
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): ProjectRepository;
}
