<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\Project\Update;

use App\Balance\Application\Project\ProjectAuthorizer;
use App\Balance\Application\Project\Update\UpdateProjectRequest;
use App\Balance\Application\Project\Update\UpdateProjectService;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\Project\ProjectStatus;
use App\Balance\Domain\Model\Project\ProjectTranslated;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Domain\Model\Project\ProjectUpdated;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdateProjectServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = ProjectRepositoryMother::inMemory();

        $project = ProjectMother::random();

        $repository->add($project);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new UpdateProjectRequest(
            projectId: $project->id(),
            name: 'changedName',
            description: 'changedDescription',
            location: DestinationId::generate(),
            gallery: [],
            projectType: ProjectType::SocialProject,
            projectStatus: ProjectStatus::Executed,
            mitecoId: 'MITECO-2',
            pricePerTonne: 25.0,
        );

        $result = $sut($request, $user);

        $projectPersisted = $repository->find($result->projectId);

        $this->assertNotNull($projectPersisted);
        $this->assertSame(25.0, $projectPersisted->pricePerTonne());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ProjectUpdated);
        $this->assertTrue($projectPersisted->id()->equals($lastEvent->projectId()));
    }

    public function testUpdateWithLocaleTranslate(): void
    {
        $repository = ProjectRepositoryMother::inMemory(Locale::Spanish);

        $project = ProjectMother::random();

        $repository->add($project);

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new UpdateProjectRequest(
            projectId: $project->id(),
            name: 'changedName',
            description: 'changedDescription',
            location: DestinationId::generate(),
            gallery: [],
            projectType: ProjectType::EmissionCompensation,
            projectStatus: ProjectStatus::Pending,
            mitecoId: null,
        );

        $result = $sut($request, $user);

        $projectPersisted = $repository->find($result->projectId);

        $this->assertNotNull($projectPersisted);
        $this->assertEquals('changedName', $projectPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ProjectTranslated);
        $this->assertTrue($projectPersisted->id()->equals($lastEvent->projectId()));
    }

    private function createSUT(ProjectRepository $repository, Locale $locale = Locale::Spanish): UpdateProjectService
    {
        FakeLocaleSetter::set($locale);

        return new UpdateProjectService(
            projectRepository: $repository,
            authorizer: new ProjectAuthorizer(),
        );
    }
}
