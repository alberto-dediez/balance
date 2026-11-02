<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint;

use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\GasBreakdown;
use PHPUnit\Framework\TestCase;

final class GasBreakdownTest extends TestCase
{
    public function testRemainderIsTheDifferenceWithTheTotal(): void
    {
        $breakdown = GasBreakdown::fromComponents(co2: 800.0, ch4: 50.0, n2o: 25.0, refrigerants: 100.0, total: 1000.0);

        $this->assertSame(25.0, $breakdown->remainder);
        $this->assertEqualsWithDelta(
            $breakdown->total,
            $breakdown->co2 + $breakdown->ch4 + $breakdown->n2o + $breakdown->refrigerants + $breakdown->remainder,
            0.01,
        );
    }

    public function testRoundingDifferencesDoNotProduceRemainder(): void
    {
        $breakdown = GasBreakdown::fromComponents(co2: 0.1 + 0.2, ch4: 0.0, n2o: 0.0, refrigerants: 0.0, total: 0.3);

        $this->assertSame(0.0, $breakdown->remainder);
    }
}
