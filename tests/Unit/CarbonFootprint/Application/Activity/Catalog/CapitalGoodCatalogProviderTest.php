<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Catalog;

use App\CarbonFootprint\Application\Activity\Catalog\CapitalGoodCatalogProvider;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use PHPUnit\Framework\TestCase;

class CapitalGoodCatalogProviderTest extends TestCase
{
    public function testActivityTypeIsCapitalGood(): void
    {
        $sut = new CapitalGoodCatalogProvider(CapitalGoodRepositoryMother::inMemory());

        $this->assertSame(ActivityType::CapitalGood, $sut->activityType());
    }

    public function testReturnsEmptyWithoutGroup(): void
    {
        $sut = new CapitalGoodCatalogProvider(CapitalGoodRepositoryMother::inMemory());

        $this->assertSame([], $sut->catalog(2024, null));
    }

    public function testReturnsOnlyCapitalGoodsWithFactorForYearAndGroup(): void
    {
        $groupId = EmissionFactorGroupMother::defaultId();

        $withFactor = CapitalGoodMother::fromName('Consultoría');
        $withFactor->setEmissionFactor(ActivityMother::emissionFactor($groupId)->withUnit(Unit::Euro));
        $withoutFactor = CapitalGoodMother::fromName('Publicidad');

        $repository = CapitalGoodRepositoryMother::inMemory();
        $repository->add($withFactor);
        $repository->add($withoutFactor);

        $sut = new CapitalGoodCatalogProvider($repository);

        $catalog = $sut->catalog(2024, $groupId);

        $this->assertCount(1, $catalog);
        $this->assertSame($withFactor, $catalog[0]);
        $this->assertSame([], $sut->catalog(2023, $groupId));
    }

    public function testSecondaryCatalogIsEmpty(): void
    {
        $sut = new CapitalGoodCatalogProvider(CapitalGoodRepositoryMother::inMemory());

        $this->assertSame([], $sut->secondaryCatalog(2024, EmissionFactorGroupMother::defaultId()));
    }
}
