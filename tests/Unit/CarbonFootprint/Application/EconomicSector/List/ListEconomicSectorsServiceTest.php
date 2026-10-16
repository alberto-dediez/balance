<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EconomicSector\List;

use App\CarbonFootprint\Application\EconomicSector\EconomicSectorAuthorizer;
use App\CarbonFootprint\Application\EconomicSector\List\ListEconomicSectorsRequest;
use App\CarbonFootprint\Application\EconomicSector\List\ListEconomicSectorsService;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListEconomicSectorsServiceTest extends TestCase
{
    protected function setUp(): void {}

    public function testInvoke(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(EconomicSectorMother::random());
        $repository->add(EconomicSectorMother::random());
        $repository->add(EconomicSectorMother::random());
        $repository->add(EconomicSectorMother::random());

        $request = new ListEconomicSectorsRequest();

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(4, $result->economicSectors);
        $this->assertEquals(4, $result->totalResults);
    }

    public function testFilterByName(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $repository->add(EconomicSectorMother::fromName('name-1'));
        $repository->add(EconomicSectorMother::fromName('name-2'));
        $repository->add(EconomicSectorMother::fromName('name-2'));
        $repository->add(EconomicSectorMother::fromName('name-2'));

        $request = new ListEconomicSectorsRequest(name: 'name-1');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(1, $result->economicSectors);
        $this->assertEquals(1, $result->totalResults);

        $request = new ListEconomicSectorsRequest(name: 'name-2');

        $result = $sut($request, UserMother::adminUser());

        $this->assertCount(3, $result->economicSectors);
        $this->assertEquals(3, $result->totalResults);
    }

    private function createSUT(EconomicSectorRepository $repository): ListEconomicSectorsService
    {
        return new ListEconomicSectorsService($repository, new EconomicSectorAuthorizer());
    }
}
