<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Catalog;

use App\CarbonFootprint\Application\Activity\Catalog\PurchaseServiceCatalogProvider;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepositoryMother;
use PHPUnit\Framework\TestCase;

class PurchaseServiceCatalogProviderTest extends TestCase
{
    public function testActivityTypeIsPurchaseService(): void
    {
        $sut = new PurchaseServiceCatalogProvider(PurchaseServiceRepositoryMother::inMemory());

        $this->assertSame(ActivityType::PurchaseService, $sut->activityType());
    }

    public function testReturnsEmptyWithoutGroup(): void
    {
        $sut = new PurchaseServiceCatalogProvider(PurchaseServiceRepositoryMother::inMemory());

        $this->assertSame([], $sut->catalog(2024, null));
    }

    public function testReturnsOnlyPurchaseServicesWithFactorForYearAndGroup(): void
    {
        $groupId = EmissionFactorGroupMother::defaultId();

        $withFactor = PurchaseServiceMother::fromName('Consultoría');
        $withFactor->setEmissionFactor(ActivityMother::emissionFactor($groupId)->withUnit(Unit::Euro));
        $withoutFactor = PurchaseServiceMother::fromName('Publicidad');

        $repository = PurchaseServiceRepositoryMother::inMemory();
        $repository->add($withFactor);
        $repository->add($withoutFactor);

        $sut = new PurchaseServiceCatalogProvider($repository);

        $catalog = $sut->catalog(2024, $groupId);

        $this->assertCount(1, $catalog);
        $this->assertSame($withFactor, $catalog[0]);
        $this->assertSame([], $sut->catalog(2023, $groupId));
    }

    public function testSecondaryCatalogIsEmpty(): void
    {
        $sut = new PurchaseServiceCatalogProvider(PurchaseServiceRepositoryMother::inMemory());

        $this->assertSame([], $sut->secondaryCatalog(2024, EmissionFactorGroupMother::defaultId()));
    }
}
