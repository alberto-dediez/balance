<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Application\Activity\EmissionsCalculator;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\IncompatibleUnitsException;
use App\CarbonFootprint\Domain\Model\Quantity;
use App\CarbonFootprint\Domain\Model\Unit;
use PHPUnit\Framework\TestCase;

class EmissionsCalculatorTest extends TestCase
{
    public function testCalculatesUsingTheQuantityUnitDirectlyWhenItMatchesTheFactor(): void
    {
        $calculator = new EmissionsCalculator();
        $factor = new EmissionFactor('1.5', '1.5', '0', '0', 2024, EmissionFactorGroupId::generate(), Unit::Kilogram);

        $emissions = $calculator->calculate($factor, new Quantity(10.0, Unit::Kilogram));

        $this->assertSame('15', $emissions->co2eq());
    }

    public function testConvertsTheQuantityToTheFactorUnitBeforeMultiplying(): void
    {
        $calculator = new EmissionsCalculator();
        $factor = new EmissionFactor('1.5', '1.5', '0', '0', 2024, EmissionFactorGroupId::generate(), Unit::Kilogram);

        $emissions = $calculator->calculate($factor, new Quantity(2.0, Unit::Tonne));

        $this->assertSame('3000', $emissions->co2eq());
    }

    public function testThrowsWhenQuantityMagnitudeIsIncompatibleWithTheFactorUnit(): void
    {
        $calculator = new EmissionsCalculator();
        $factor = new EmissionFactor('1.5', '1.5', '0', '0', 2024, EmissionFactorGroupId::generate(), Unit::Kilogram);

        $this->expectException(IncompatibleUnitsException::class);
        $calculator->calculate($factor, new Quantity(10.0, Unit::Liter));
    }
}
