<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Catalog;

use App\CarbonFootprint\Application\Activity\Catalog\RefrigerantGasCatalogProvider;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepositoryMother;
use PHPUnit\Framework\TestCase;

class RefrigerantGasCatalogProviderTest extends TestCase
{
    public function testReturnsEmptyWithoutGroup(): void
    {
        $sut = new RefrigerantGasCatalogProvider(RefrigerantGasRepositoryMother::inMemory());

        $this->assertSame([], $sut->catalog(2024, null));
    }

    public function testReturnsOnlyGasesWithFactorForYearAndGroup(): void
    {
        $groupId = EmissionFactorGroupMother::default()->id();

        $withFactor = RefrigerantGasMother::fromName('R-410A');
        $withFactor->setEmissionFactor(ActivityMother::emissionFactor($groupId));
        $withoutFactor = RefrigerantGasMother::fromName('R-32');

        $repository = RefrigerantGasRepositoryMother::inMemory();
        $repository->add($withFactor);
        $repository->add($withoutFactor);

        $sut = new RefrigerantGasCatalogProvider($repository);

        $catalog = $sut->catalog(2024, $groupId);

        $this->assertCount(1, $catalog);
        $this->assertSame($withFactor, $catalog[0]);
    }

    public function testSecondaryCatalogIsEmpty(): void
    {
        $sut = new RefrigerantGasCatalogProvider(RefrigerantGasRepositoryMother::inMemory());

        $this->assertSame([], $sut->secondaryCatalog(2024, EmissionFactorGroupMother::default()->id()));
    }
}
