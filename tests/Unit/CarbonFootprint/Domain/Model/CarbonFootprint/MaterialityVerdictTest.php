<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityVerdict;
use PHPUnit\Framework\TestCase;

final class MaterialityVerdictTest extends TestCase
{
    public function testStyle(): void
    {
        $this->assertSame('bg-success', MaterialityVerdict::Calculate->style());
        $this->assertSame('bg-secondary', MaterialityVerdict::Omit->style());
    }

    public function testOnlyOmitRecommendsExclusion(): void
    {
        $this->assertTrue(MaterialityVerdict::Omit->recommendsExclusion());
        $this->assertFalse(MaterialityVerdict::Calculate->recommendsExclusion());
    }
}
