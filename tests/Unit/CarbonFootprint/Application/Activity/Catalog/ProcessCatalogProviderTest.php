<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Catalog;

use App\CarbonFootprint\Application\Activity\Catalog\ProcessCatalogProvider;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Process\ProcessMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Process\ProcessRepositoryMother;
use PHPUnit\Framework\TestCase;

class ProcessCatalogProviderTest extends TestCase
{
    public function testActivityTypeIsProcess(): void
    {
        $sut = new ProcessCatalogProvider(ProcessRepositoryMother::inMemory());

        $this->assertSame(ActivityType::Process, $sut->activityType());
    }

    public function testReturnsEmptyWithoutGroup(): void
    {
        $sut = new ProcessCatalogProvider(ProcessRepositoryMother::inMemory());

        $this->assertSame([], $sut->catalog(2024, null));
    }

    public function testReturnsOnlyProcessesWithFactorForYearAndGroup(): void
    {
        $groupId = EmissionFactorGroupMother::defaultId();

        $withFactor = ProcessMother::fromName('Mecanizado de pieza');
        $withFactor->setEmissionFactor(ActivityMother::emissionFactor($groupId)->withUnit(Unit::Piece));
        $withoutFactor = ProcessMother::fromName('Publicidad');

        $repository = ProcessRepositoryMother::inMemory();
        $repository->add($withFactor);
        $repository->add($withoutFactor);

        $sut = new ProcessCatalogProvider($repository);

        $catalog = $sut->catalog(2024, $groupId);

        $this->assertCount(1, $catalog);
        $this->assertSame($withFactor, $catalog[0]);
        $this->assertSame([], $sut->catalog(2023, $groupId));
    }

    public function testSecondaryCatalogIsEmpty(): void
    {
        $sut = new ProcessCatalogProvider(ProcessRepositoryMother::inMemory());

        $this->assertSame([], $sut->secondaryCatalog(2024, EmissionFactorGroupMother::defaultId()));
    }
}
