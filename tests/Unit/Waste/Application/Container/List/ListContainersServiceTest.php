<?php

namespace App\Tests\Unit\Waste\Application\Container\List;

use PHPUnit\Framework\TestCase;
use App\Waste\Application\Container\List\ListContainersRequest;
use App\Waste\Application\Container\List\ListContainersService;
use App\Waste\Application\Container\List\ListContainersResponse;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerMother;
use App\Waste\Infrastructure\Repository\Container\InMemoryContainerRepository;
use App\Waste\Infrastructure\Repository\Property\InMemoryPropertyRepository;

class ListContainersServiceTest extends TestCase
{
    protected function setUp(): void
    {
        
    }

    public function testInvoke()
    {
        $repository = new InMemoryContainerRepository(
            new InMemoryPropertyRepository()
        );

        $sut = $this->createSUT($repository);

        $repository->add(ContainerMother::random());
        $repository->add(ContainerMother::random());
        $repository->add(ContainerMother::random());
        $repository->add(ContainerMother::random());

        $request = new ListContainersRequest();

        $result = $sut($request);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof ListContainersResponse);
        $this->assertCount(4, $result->containers);
        $this->assertEquals(4, $result->totalResults);
    }

    private function createSUT($repository)
    {
        return new ListContainersService($repository);
    }
}