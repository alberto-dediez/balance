<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint;

use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityDenominators;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicatorsCalculator;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicatorValue;
use PHPUnit\Framework\TestCase;

final class IntensityIndicatorsCalculatorTest extends TestCase
{
    public function testCalculatesOnlyIndicatorsWithDenominator(): void
    {
        $values = (new IntensityIndicatorsCalculator())->calculate(
            184320.55,
            new IntensityDenominators(stays: 41000, builtAreaM2: '2000.00', revenueEur: '3500000.00'),
        );

        $this->assertSame(
            [IntensityIndicator::PerStay, IntensityIndicator::PerSquareMeter, IntensityIndicator::PerMillionEuroRevenue],
            array_map(static fn(IntensityIndicatorValue $value): IntensityIndicator => $value->indicator, $values),
        );
        $this->assertSame(4.5, $values[0]->kgCo2eqPerUnit);
        $this->assertSame(41000.0, $values[0]->denominator);
        $this->assertSame(92.16, $values[1]->kgCo2eqPerUnit);
        $this->assertSame(52663.01, $values[2]->kgCo2eqPerUnit);
    }

    public function testValuesBelowOneKeepFourDecimals(): void
    {
        $values = (new IntensityIndicatorsCalculator())->calculate(1234.5, new IntensityDenominators(customersServed: 3000));

        $this->assertSame(0.4115, $values[0]->kgCo2eqPerUnit);
    }

    public function testWithoutDenominatorsReturnsNothing(): void
    {
        $this->assertSame([], (new IntensityIndicatorsCalculator())->calculate(1000.0, IntensityDenominators::empty()));
    }
}
