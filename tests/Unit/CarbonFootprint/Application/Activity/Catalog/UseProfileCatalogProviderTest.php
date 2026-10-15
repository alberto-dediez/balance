<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Catalog;

use App\CarbonFootprint\Application\Activity\Catalog\UseProfileCatalogProvider;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile\UseProfileMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepositoryMother;
use PHPUnit\Framework\TestCase;

class UseProfileCatalogProviderTest extends TestCase
{
    public function testActivityTypeIsUseProfile(): void
    {
        $sut = new UseProfileCatalogProvider(UseProfileRepositoryMother::inMemory());

        $this->assertSame(ActivityType::UseProfile, $sut->activityType());
    }

    public function testReturnsEmptyWithoutGroup(): void
    {
        $sut = new UseProfileCatalogProvider(UseProfileRepositoryMother::inMemory());

        $this->assertSame([], $sut->catalog(2024, null));
    }

    public function testReturnsOnlyUseProfilesWithFactorForYearAndGroup(): void
    {
        $groupId = EmissionFactorGroupMother::defaultId();

        $withFactor = UseProfileMother::fromName('Lavadora clase A');
        $withFactor->setEmissionFactor(ActivityMother::emissionFactor($groupId)->withUnit(Unit::Piece));
        $withoutFactor = UseProfileMother::fromName('Publicidad');

        $repository = UseProfileRepositoryMother::inMemory();
        $repository->add($withFactor);
        $repository->add($withoutFactor);

        $sut = new UseProfileCatalogProvider($repository);

        $catalog = $sut->catalog(2024, $groupId);

        $this->assertCount(1, $catalog);
        $this->assertSame($withFactor, $catalog[0]);
        $this->assertSame([], $sut->catalog(2023, $groupId));
    }

    public function testSecondaryCatalogIsEmpty(): void
    {
        $sut = new UseProfileCatalogProvider(UseProfileRepositoryMother::inMemory());

        $this->assertSame([], $sut->secondaryCatalog(2024, EmissionFactorGroupMother::defaultId()));
    }
}
