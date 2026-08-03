<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\Project\Remove;

use App\Balance\Application\Project\ProjectAuthorizer;
use App\Balance\Application\Project\Remove\RemoveProjectRequest;
use App\Balance\Application\Project\Remove\RemoveProjectService;
use App\Balance\Domain\Model\Project\ProjectRemoved;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveProjectServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = ProjectRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $project = ProjectMother::registeredProject();

        $repository->add($project);

        $request = new RemoveProjectRequest(
            projectId: $project->id(),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($project->id()->equals($result->projectId));

        $projectPersisted = $repository->find($result->projectId);

        $this->assertNull($projectPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof ProjectRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = ProjectRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $project = ProjectMother::random();

        $result = $sut->isHandled(new RemoveProjectRequest(
            projectId: $project->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(ProjectRepository $repository): RemoveProjectService
    {
        return new RemoveProjectService($repository, new ProjectAuthorizer());
    }
}
