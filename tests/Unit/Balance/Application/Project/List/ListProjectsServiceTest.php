<?php

declare(strict_types=1);

namespace App\Tests\Unit\Balance\Application\Project\List;

use App\Balance\Application\Project\List\ListProjectsRequest;
use App\Balance\Application\Project\List\ListProjectsService;
use App\Balance\Application\Project\ProjectAuthorizer;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectMother;
use App\Tests\Unit\Balance\Domain\Model\Project\ProjectRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListProjectsServiceTest extends TestCase
{
    public function testInvoke(): void
    {
        $repository = ProjectRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(ProjectMother::random());
        $repository->add(ProjectMother::random());
        $repository->add(ProjectMother::random());
        $repository->add(ProjectMother::random());

        $request = new ListProjectsRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(4, $result->projects);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByName(): void
    {
        $repository = ProjectRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(ProjectMother::fromName('name-1'));
        $repository->add(ProjectMother::fromName('name-2'));
        $repository->add(ProjectMother::fromName('name-2'));
        $repository->add(ProjectMother::fromName('name-2'));

        $request = new ListProjectsRequest(name: 'name-1');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(1, $result->projects);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListProjectsRequest(name: 'name-2');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(3, $result->projects);
        $this->assertEquals(3, $result->totalResults);
    }

    private function createSUT(ProjectRepository $repository): ListProjectsService
    {
        return new ListProjectsService($repository, new ProjectAuthorizer());
    }
}
