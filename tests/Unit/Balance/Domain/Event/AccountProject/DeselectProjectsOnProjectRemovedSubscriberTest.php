<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Domain\Event\AccountProject;

use App\Balance\Domain\Event\AccountProject\DeselectProjectsOnProjectRemovedSubscriber;
use App\Balance\Domain\Model\AccountProject\AccountProjectFilter;
use App\Balance\Domain\Model\AccountProject\AccountProjectRepository;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRegistered;
use App\Balance\Domain\Model\Project\ProjectRemoved;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectMother;
use App\Tests\Unit\Balance\Domain\Model\AccountProject\AccountProjectRepositoryMother;
use PHPUnit\Framework\TestCase;

class DeselectProjectsOnProjectRemovedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToProjectRemoved(): void
    {
        $sut = $this->createSUT(AccountProjectRepositoryMother::inMemory());

        $this->assertTrue($sut->isSubscribedTo(new ProjectRemoved(ProjectId::generate())));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $sut = $this->createSUT(AccountProjectRepositoryMother::inMemory());

        $this->assertFalse($sut->isSubscribedTo(new ProjectRegistered(
            projectId: ProjectId::generate(),
            name: 'name',
            description: 'description',
            location: null,
            gallery: [],
            projectType: null,
            projectStatus: null,
            mitecoId: null,
        )));
    }

    public function testHandleRemovesAccountProjectsLinkedToProject(): void
    {
        $repository = AccountProjectRepositoryMother::inMemory();

        $projectId = ProjectId::generate();

        $accountProject = AccountProjectMother::from(AccountId::generate(), $projectId);
        $repository->add($accountProject);

        $other = AccountProjectMother::random();
        $repository->add($other);

        $sut = $this->createSUT($repository);
        $sut->handle(new ProjectRemoved($projectId));

        $this->assertCount(0, $repository->findByFilter(new AccountProjectFilter(projectId: $projectId, limit: 0)));
        $this->assertNotNull($repository->find($other->id()));
    }

    private function createSUT(AccountProjectRepository $accountProjectRepository): DeselectProjectsOnProjectRemovedSubscriber
    {
        return new DeselectProjectsOnProjectRemovedSubscriber($accountProjectRepository);
    }
}
