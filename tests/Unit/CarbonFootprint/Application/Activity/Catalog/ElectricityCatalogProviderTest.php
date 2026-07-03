<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity\Catalog;

use App\CarbonFootprint\Application\Activity\Catalog\ElectricityCatalogProvider;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryElectricityMarketerRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryGdOFactorRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
use PHPUnit\Framework\TestCase;

class ElectricityCatalogProviderTest extends TestCase
{
    private InMemoryElectricityMarketerRepository $electricityMarketerRepository;
    private InMemoryGdOFactorRepository $gdOFactorRepository;
    private ElectricityCatalogProvider $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->electricityMarketerRepository = ElectricityMarketerRepositoryMother::inMemory();
        $this->gdOFactorRepository = GdOFactorRepositoryMother::inMemory();

        $this->sut = new ElectricityCatalogProvider($this->electricityMarketerRepository, $this->gdOFactorRepository);
    }

    public function testActivityTypeIsElectricity(): void
    {
        $this->assertSame(ActivityType::Electricity, $this->sut->activityType());
    }

    public function testCatalogReturnsOnlyMarketersHavingEmissionFactorForTheYearAndGroup(): void
    {
        $groupId = EmissionFactorGroupMother::defaultId();

        $withFactor = ElectricityMarketerMother::registeredElectricityMarketer();
        $withFactor->setEmissionFactor(ActivityMother::emissionFactor($groupId)->withYear(2026));

        $withoutFactor = ElectricityMarketerMother::registeredElectricityMarketer();

        $this->electricityMarketerRepository->add($withFactor);
        $this->electricityMarketerRepository->add($withoutFactor);

        $this->assertSame([$withFactor], $this->sut->catalog(2026, $groupId));
    }

    public function testCatalogIsEmptyWhenNoEmissionFactorGroupIsGiven(): void
    {
        $withFactor = ElectricityMarketerMother::registeredElectricityMarketer();
        $withFactor->setEmissionFactor(ActivityMother::emissionFactor()->withYear(2026));
        $this->electricityMarketerRepository->add($withFactor);

        $this->assertSame([], $this->sut->catalog(2026, null));
    }

    public function testSecondaryCatalogReturnsAllGdOFactorsUnfiltered(): void
    {
        $first = GdOFactorMother::registeredGdOFactor();
        $second = GdOFactorMother::registeredGdOFactor();
        $this->gdOFactorRepository->add($first);
        $this->gdOFactorRepository->add($second);

        $secondaryCatalog = $this->sut->secondaryCatalog();

        $this->assertCount(2, $secondaryCatalog);
        $this->assertContains($first, $secondaryCatalog);
        $this->assertContains($second, $secondaryCatalog);
    }
}
