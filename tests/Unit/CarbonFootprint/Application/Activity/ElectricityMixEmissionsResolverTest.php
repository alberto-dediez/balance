<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Application\Activity\ElectricityMixEmissionsResolver;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMix;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Property\Domain\Model\Country\CountryId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use PHPUnit\Framework\TestCase;

class ElectricityMixEmissionsResolverTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testReturnsNullWithoutCountry(): void
    {
        $sut = $this->createSUT(ElectricityMixRepositoryMother::inMemory());

        $this->assertNull($sut->resolve(null, 2024, EmissionFactorGroupMother::defaultId(), $this->quantity()));
    }

    public function testReturnsNullWhenThereIsNoMixForTheCountry(): void
    {
        $sut = $this->createSUT(ElectricityMixRepositoryMother::inMemory());

        $this->assertNull($sut->resolve(new CountryId('C-ES'), 2024, EmissionFactorGroupMother::defaultId(), $this->quantity()));
    }

    public function testReturnsNullWhenTheMixHasNoFactorForTheYearAndGroup(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();
        $repository->add($this->spanishMixWithFactorForYear(2023));

        $sut = $this->createSUT($repository);

        $this->assertNull($sut->resolve(new CountryId('C-ES'), 2024, EmissionFactorGroupMother::defaultId(), $this->quantity()));
    }

    public function testCalculatesTheLocationBasedResult(): void
    {
        $electricityMix = $this->spanishMixWithFactorForYear(2024);

        $repository = ElectricityMixRepositoryMother::inMemory();
        $repository->add($electricityMix);

        $sut = $this->createSUT($repository);

        $result = $sut->resolve(new CountryId('C-ES'), 2024, EmissionFactorGroupMother::defaultId(), $this->quantity());

        $this->assertNotNull($result);
        $this->assertTrue($electricityMix->id()->equals($result->electricityMixId));
        $this->assertSame('0.18', $result->emissionFactor->co2eq());
        $this->assertSame(Unit::KWh, $result->emissionFactor->unit());
        $this->assertSame(180.0, (float) $result->emissions->co2eq());
    }

    public function testCalculatesTheLocationBasedWellToTankResult(): void
    {
        $electricityMix = ElectricityMixMother::fromCountryAndId('C-ES', (string) ElectricityMixId::generate());
        $electricityMix->setEmissionFactor(
            new EmissionFactor('0.18', '0.18', '0', '0', 2024, EmissionFactorGroupMother::defaultId(), Unit::KWh, 'REE'),
            new EmissionFactor('0.03', '0.03', '0', '0', 2024, EmissionFactorGroupMother::defaultId(), Unit::KWh, 'DEFRA'),
        );

        $repository = ElectricityMixRepositoryMother::inMemory();
        $repository->add($electricityMix);

        $sut = $this->createSUT($repository);

        $result = $sut->resolve(new CountryId('C-ES'), 2024, EmissionFactorGroupMother::defaultId(), $this->quantity());

        $this->assertNotNull($result);
        $this->assertSame('0.03', $result->wellToTankEmissionFactor->co2eq());
        $this->assertSame('DEFRA', $result->wellToTankEmissionFactor->source());
        $this->assertSame(30.0, (float) $result->wellToTankEmissions->co2eq());
    }

    public function testMixWithoutWellToTankYieldsZeroWellToTankEmissions(): void
    {
        $repository = ElectricityMixRepositoryMother::inMemory();
        $repository->add($this->spanishMixWithFactorForYear(2024));

        $sut = $this->createSUT($repository);

        $result = $sut->resolve(new CountryId('C-ES'), 2024, EmissionFactorGroupMother::defaultId(), $this->quantity());

        $this->assertNotNull($result);
        $this->assertSame(0.0, (float) $result->wellToTankEmissions->co2eq());
    }

    private function spanishMixWithFactorForYear(int $year): ElectricityMix
    {
        $electricityMix = ElectricityMixMother::fromCountryAndId('C-ES', (string) ElectricityMixId::generate());

        $electricityMix->setEmissionFactor(new EmissionFactor(
            co2eq: '0.18',
            co2: '0.18',
            ch4: '0',
            n2o: '0',
            year: $year,
            emissionFactorGroupId: EmissionFactorGroupMother::defaultId(),
            unit: Unit::KWh,
            source: 'REE',
        ));

        return $electricityMix;
    }

    private function quantity(): Quantity
    {
        return new Quantity(1000.0, Unit::KWh);
    }

    private function createSUT(ElectricityMixRepository $repository): ElectricityMixEmissionsResolver
    {
        return new ElectricityMixEmissionsResolver($repository, EmissionsCalculatorMother::create());
    }
}
