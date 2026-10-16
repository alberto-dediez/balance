<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Catalog;

use App\CarbonFootprint\Application\Activity\Catalog\EconomicSectorCatalogProvider;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use PHPUnit\Framework\TestCase;

class EconomicSectorCatalogProviderTest extends TestCase
{
    public function testActivityTypeIsEconomicSector(): void
    {
        $sut = new EconomicSectorCatalogProvider(EconomicSectorRepositoryMother::inMemory());

        $this->assertSame(ActivityType::EconomicSector, $sut->activityType());
    }

    public function testReturnsEmptyWithoutGroup(): void
    {
        $sut = new EconomicSectorCatalogProvider(EconomicSectorRepositoryMother::inMemory());

        $this->assertSame([], $sut->catalog(2024, null));
    }

    public function testReturnsOnlyEconomicSectorsWithFactorForYearAndGroup(): void
    {
        $groupId = EmissionFactorGroupMother::defaultId();

        $withFactor = EconomicSectorMother::fromName('Fabricación de productos metálicos');
        $withFactor->setEmissionFactor(ActivityMother::emissionFactor($groupId)->withUnit(Unit::Euro));
        $withoutFactor = EconomicSectorMother::fromName('Publicidad');

        $repository = EconomicSectorRepositoryMother::inMemory();
        $repository->add($withFactor);
        $repository->add($withoutFactor);

        $sut = new EconomicSectorCatalogProvider($repository);

        $catalog = $sut->catalog(2024, $groupId);

        $this->assertCount(1, $catalog);
        $this->assertSame($withFactor, $catalog[0]);
        $this->assertSame([], $sut->catalog(2023, $groupId));
    }

    public function testSecondaryCatalogIsEmpty(): void
    {
        $sut = new EconomicSectorCatalogProvider(EconomicSectorRepositoryMother::inMemory());

        $this->assertSame([], $sut->secondaryCatalog(2024, EmissionFactorGroupMother::defaultId()));
    }
}
