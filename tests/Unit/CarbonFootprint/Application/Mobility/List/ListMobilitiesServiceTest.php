<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Mobility\List;

use App\CarbonFootprint\Application\Mobility\List\ListMobilitiesRequest;
use App\CarbonFootprint\Application\Mobility\List\ListMobilitiesService;
use App\CarbonFootprint\Application\Mobility\MobilityAuthorizer;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListMobilitiesServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = MobilityRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(MobilityMother::random());
        $repository->add(MobilityMother::random());
        $repository->add(MobilityMother::random());
        $repository->add(MobilityMother::random());

        $request = new ListMobilitiesRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(4, $result->mobilities);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByName(): void
    {
        $repository = MobilityRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(MobilityMother::fromName('name-1'));
        $repository->add(MobilityMother::fromName('name-2'));
        $repository->add(MobilityMother::fromName('name-2'));
        $repository->add(MobilityMother::fromName('name-2'));

        $request = new ListMobilitiesRequest(name: 'name-1');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(1, $result->mobilities);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListMobilitiesRequest(name: 'name-2');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(3, $result->mobilities);
        $this->assertEquals(3, $result->totalResults);
    }

    private function createSUT(MobilityRepository $repository): ListMobilitiesService
    {
        return new ListMobilitiesService($repository, new MobilityAuthorizer());
    }
}
