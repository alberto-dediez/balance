<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\RoadTransport\List;

use App\CarbonFootprint\Application\RoadTransport\List\ListRoadTransportsRequest;
use App\CarbonFootprint\Application\RoadTransport\List\ListRoadTransportsService;
use App\CarbonFootprint\Application\RoadTransport\RoadTransportAuthorizer;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListRoadTransportsServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(RoadTransportMother::random());
        $repository->add(RoadTransportMother::random());
        $repository->add(RoadTransportMother::random());
        $repository->add(RoadTransportMother::random());

        $request = new ListRoadTransportsRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(4, $result->roadTransports);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByName(): void
    {
        $repository = RoadTransportRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(RoadTransportMother::fromName('name-1'));
        $repository->add(RoadTransportMother::fromName('name-2'));
        $repository->add(RoadTransportMother::fromName('name-2'));
        $repository->add(RoadTransportMother::fromName('name-2'));

        $request = new ListRoadTransportsRequest(name: 'name-1');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(1, $result->roadTransports);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListRoadTransportsRequest(name: 'name-2');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(3, $result->roadTransports);
        $this->assertEquals(3, $result->totalResults);
    }

    private function createSUT(RoadTransportRepository $repository): ListRoadTransportsService
    {
        return new ListRoadTransportsService($repository, new RoadTransportAuthorizer());
    }
}
