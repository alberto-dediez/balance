<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Process\List;

use App\CarbonFootprint\Application\Process\List\ListProcessesRequest;
use App\CarbonFootprint\Application\Process\List\ListProcessesService;
use App\CarbonFootprint\Application\Process\ProcessAuthorizer;
use App\CarbonFootprint\Domain\Model\Process\ProcessRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Process\ProcessMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Process\ProcessRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListProcessesServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = ProcessRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(ProcessMother::random());
        $repository->add(ProcessMother::random());
        $repository->add(ProcessMother::random());
        $repository->add(ProcessMother::random());

        $request = new ListProcessesRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(4, $result->processes);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByName(): void
    {
        $repository = ProcessRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(ProcessMother::fromName('name-1'));
        $repository->add(ProcessMother::fromName('name-2'));
        $repository->add(ProcessMother::fromName('name-2'));
        $repository->add(ProcessMother::fromName('name-2'));

        $request = new ListProcessesRequest(name: 'name-1');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(1, $result->processes);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListProcessesRequest(name: 'name-2');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(3, $result->processes);
        $this->assertEquals(3, $result->totalResults);
    }

    private function createSUT(ProcessRepository $repository): ListProcessesService
    {
        return new ListProcessesService($repository, new ProcessAuthorizer());
    }
}
