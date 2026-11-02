<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CalculationMethod;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\InterannualVariationDecomposer;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\InventoryLine;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\Unit;
use PHPUnit\Framework\TestCase;

final class InterannualVariationDecomposerTest extends TestCase
{
    public function testDecomposesIntoActivityFactorAndEfficiency(): void
    {
        $variation = (new InterannualVariationDecomposer())->decompose(
            2024,
            100000.0,
            [$this->naturalGas(50000.0, 0.20)],
            2025,
            96000.0,
            [$this->naturalGas(48000.0, 0.18)],
            IntensityIndicator::PerStay,
            40000.0,
            44000.0,
        );

        $this->assertSame(2024, $variation->fromYear);
        $this->assertSame(2025, $variation->toYear);
        $this->assertEqualsWithDelta(-4000.0, $variation->delta, 0.0001);
        $this->assertNotNull($variation->activityEffect);
        $this->assertEqualsWithDelta(10000.0, $variation->activityEffect, 0.0001);
        $this->assertEqualsWithDelta(-960.0, $variation->factorEffect, 0.0001);
        $this->assertEqualsWithDelta(-13040.0, $variation->efficiencyEffect, 0.0001);
        $this->assertEqualsWithDelta($variation->delta, $variation->activityEffect + $variation->factorEffect + $variation->efficiencyEffect, 0.0001);
    }

    public function testWithoutDenominatorThereIsNoActivityEffect(): void
    {
        $variation = (new InterannualVariationDecomposer())->decompose(
            2024,
            100000.0,
            [$this->naturalGas(50000.0, 0.20)],
            2025,
            96000.0,
            [$this->naturalGas(48000.0, 0.18)],
            IntensityIndicator::PerStay,
            null,
            44000.0,
        );

        $this->assertNull($variation->activityEffect);
        $this->assertEqualsWithDelta(-960.0, $variation->factorEffect, 0.0001);
        $this->assertEqualsWithDelta(-4000.0 + 960.0, $variation->efficiencyEffect, 0.0001);
    }

    public function testLinesWithoutCounterpartDoNotEnterTheFactorEffect(): void
    {
        $variation = (new InterannualVariationDecomposer())->decompose(
            2024,
            10000.0,
            [$this->naturalGas(50000.0, 0.20)],
            2025,
            12000.0,
            [
                $this->naturalGas(50000.0, 0.20),
                new InventoryLine(Scope::Scope1, Source::FixedFacilities, 'Gasóleo', Unit::Liter, 1000.0, 2.5, 2500.0, CalculationMethod::ActivityBased),
                new InventoryLine(Scope::Scope3, Source::FuelAndEnergyRelated, 'WTT', null, null, null, 300.0, CalculationMethod::DerivedWellToTank),
            ],
            null,
            null,
            null,
        );

        $this->assertSame(0.0, $variation->factorEffect);
        $this->assertNull($variation->activityEffect);
        $this->assertEqualsWithDelta(2000.0, $variation->efficiencyEffect, 0.0001);
    }

    private function naturalGas(float $quantity, float $factor): InventoryLine
    {
        return new InventoryLine(
            scope: Scope::Scope1,
            source: Source::FixedFacilities,
            itemName: 'Gas natural',
            unit: Unit::KWh,
            quantity: $quantity,
            factorCo2eq: $factor,
            emissionsCo2eq: $quantity * $factor,
            method: CalculationMethod::ActivityBased,
        );
    }
}
