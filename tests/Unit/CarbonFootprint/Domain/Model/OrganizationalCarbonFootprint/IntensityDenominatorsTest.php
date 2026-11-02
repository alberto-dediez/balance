<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint;

use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityDenominators;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;
use PHPUnit\Framework\TestCase;

final class IntensityDenominatorsTest extends TestCase
{
    public function testEmptyHasNoDenominators(): void
    {
        $denominators = IntensityDenominators::empty();

        $this->assertTrue($denominators->isEmpty());
        foreach (IntensityIndicator::cases() as $indicator) {
            $this->assertNull($denominators->denominatorFor($indicator));
        }
    }

    public function testZeroThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new IntensityDenominators(stays: 0);
    }

    public function testNegativeDecimalThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new IntensityDenominators(builtAreaM2: '-10.00');
    }

    public function testRevenueIsNormalizedToMillionEuros(): void
    {
        $denominators = new IntensityDenominators(revenueEur: '3500000.00');

        $this->assertFalse($denominators->isEmpty());
        $this->assertSame(3.5, $denominators->denominatorFor(IntensityIndicator::PerMillionEuroRevenue));
    }

    public function testEqualsComparesEveryField(): void
    {
        $denominators = new IntensityDenominators(stays: 41000, builtAreaM2: '2000.00');

        $this->assertTrue($denominators->equals(new IntensityDenominators(stays: 41000, builtAreaM2: '2000')));
        $this->assertFalse($denominators->equals(new IntensityDenominators(stays: 41000)));
        $this->assertFalse($denominators->equals(new IntensityDenominators(stays: 41001, builtAreaM2: '2000.00')));
    }

    public function testSumAddsFieldByFieldAndKeepsNullWhenNoneHasValue(): void
    {
        $sum = IntensityDenominators::sum([
            new IntensityDenominators(stays: 41000, builtAreaM2: '2000.00'),
            new IntensityDenominators(stays: 12000),
        ]);

        $this->assertSame(53000, $sum->stays());
        $this->assertSame('2000.00', $sum->builtAreaM2());
        $this->assertNull($sum->occupiedRooms());
        $this->assertNull($sum->coversServed());
        $this->assertNull($sum->averageHeadcount());
        $this->assertNull($sum->revenueEur());
        $this->assertNull($sum->customersServed());
    }

    public function testSumOfNothingIsEmpty(): void
    {
        $this->assertTrue(IntensityDenominators::sum([])->isEmpty());
    }
}
