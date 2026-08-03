<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\Project\Register;

use App\Balance\Application\Project\ProjectAuthorizer;
use App\Balance\Application\Project\Register\RegisterProjectRequest;
use App\Balance\Application\Project\Register\RegisterProjectService;
use App\Balance\Domain\Model\Project\ProjectRegistered;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\Project\ProjectStatus;
use App\Balance\Domain\Model\Project\ProjectTranslated;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RegisterProjectServiceTest extends TestCase
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
        $repository = ProjectRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new RegisterProjectRequest(
            name: 'Project 1',
            description: 'Project description',
            location: DestinationId::generate(),
            gallery: [],
            projectType: ProjectType::EmissionCompensation,
            projectStatus: ProjectStatus::Pending,
            mitecoId: 'MITECO-1',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->projectId);
        $this->assertEquals(36, strlen((string) $result->projectId));
        $this->assertEquals('Project 1', $result->name);

        $projectPersisted = $repository->find($result->projectId);

        $this->assertNotNull($projectPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ProjectRegistered);
        $this->assertEquals('Project 1', $lastEvent->name());
        $this->assertTrue($projectPersisted->id()->equals($lastEvent->projectId()));
    }

    public function testRegisterWithLocaleTranslate(): void
    {
        $repository = ProjectRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new RegisterProjectRequest(
            name: 'changedName',
            description: 'changedDescription',
            location: DestinationId::generate(),
            gallery: [],
            projectType: ProjectType::SocialProject,
            projectStatus: ProjectStatus::InProgress,
            mitecoId: null,
        );

        $result = $sut($request, UserMother::adminUser());

        $projectPersisted = $repository->find($result->projectId);

        $this->assertNotNull($projectPersisted);
        $this->assertEquals('changedName', $projectPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ProjectTranslated);
        $this->assertEquals('changedName', $lastEvent->name());
        $this->assertEquals(Locale::English, $lastEvent->locale());
        $this->assertTrue($projectPersisted->id()->equals($lastEvent->projectId()));
    }

    public function testIsHandle(): void
    {
        $repository = ProjectRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut->isHandled(new RegisterProjectRequest(
            name: 'test',
            description: 'test',
            location: DestinationId::generate(),
            gallery: [],
            projectType: ProjectType::EmissionCompensation,
            projectStatus: ProjectStatus::Pending,
            mitecoId: null,
        ));

        $this->assertTrue($result);
    }

    private function createSUT(ProjectRepository $repository, Locale $locale): RegisterProjectService
    {
        FakeLocaleSetter::set($locale);

        return new RegisterProjectService(
            projectRepository: $repository,
            authorizer: new ProjectAuthorizer(),
        );
    }
}
