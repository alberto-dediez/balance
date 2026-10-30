<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\DataQuality;
use PHPUnit\Framework\TestCase;

final class DataQualityTest extends TestCase
{
    public function testTheRankGrowsAsTheQualityWorsens(): void
    {
        $this->assertSame(1, DataQuality::High->rank());
        $this->assertSame(2, DataQuality::Medium->rank());
        $this->assertSame(3, DataQuality::Low->rank());
    }

    public function testWorstOfReturnsTheLowestQuality(): void
    {
        $this->assertSame(DataQuality::Low, DataQuality::worstOf(DataQuality::High, DataQuality::Low, DataQuality::Medium));
        $this->assertSame(DataQuality::Medium, DataQuality::worstOf(DataQuality::High, DataQuality::Medium));
        $this->assertSame(DataQuality::High, DataQuality::worstOf(DataQuality::High));
    }

    public function testEveryQualityHasALabel(): void
    {
        foreach (DataQuality::cases() as $quality) {
            $this->assertNotSame('', $quality->label());
        }
    }
}
