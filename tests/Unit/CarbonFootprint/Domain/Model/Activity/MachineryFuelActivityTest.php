<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Activity;

use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use PHPUnit\Framework\TestCase;

class MachineryFuelActivityTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testRegisterWithoutWellToTankStoresZeroWellToTank(): void
    {
        $activity = MachineryFuelActivityMother::registered();

        $wellToTankEmissionFactor = $activity->wellToTankEmissionFactor();

        $this->assertSame('0', $wellToTankEmissionFactor->co2eq());
        $this->assertSame('0', $wellToTankEmissionFactor->ch4());
        $this->assertSame($activity->emissionFactor()->year(), $wellToTankEmissionFactor->year());
        $this->assertTrue($activity->emissionFactor()->emissionFactorGroupId()->equals($wellToTankEmissionFactor->emissionFactorGroupId()));
        $this->assertSame($activity->emissionFactor()->unit(), $wellToTankEmissionFactor->unit());
        $this->assertNull($wellToTankEmissionFactor->source());
        $this->assertSame('0', $activity->wellToTankEmissions()->co2eq());
    }

    public function testRegisterWithWellToTankExposesIt(): void
    {
        $activity = MachineryFuelActivityMother::registeredWithWellToTank();

        $this->assertSame('0.61', $activity->wellToTankEmissionFactor()->co2eq());
        $this->assertSame('DEFRA', $activity->wellToTankEmissionFactor()->source());
        $this->assertSame('122.0', $activity->wellToTankEmissions()->co2eq());
        $this->assertSame('15.0', $activity->emissions()->co2eq());
    }

    public function testUpdateReplacesWellToTank(): void
    {
        $activity = MachineryFuelActivityMother::registeredWithWellToTank();

        $activity->update(
            machineryFuelId: MachineryFuelId::generate(),
            emissionFactor: ActivityMother::emissionFactor(),
            emissions: ActivityMother::emissions(),
            quantity: ActivityMother::quantity(Unit::Liter, 50.0),
        );

        $this->assertSame('0', $activity->wellToTankEmissionFactor()->co2eq());
        $this->assertSame('0', $activity->wellToTankEmissions()->co2eq());
    }
}
