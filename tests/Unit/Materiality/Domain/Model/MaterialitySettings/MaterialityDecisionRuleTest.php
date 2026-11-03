<?php

declare(strict_types=1);

namespace App\Tests\Unit\Materiality\Domain\Model\MaterialitySettings;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityVerdict;
use App\Materiality\Domain\Model\MaterialitySettings\InvalidRelevanceThresholdException;
use App\Materiality\Domain\Model\MaterialitySettings\MaterialityDecisionRule;
use PHPUnit\Framework\TestCase;

class MaterialityDecisionRuleTest extends TestCase
{
    public function testDefault(): void
    {
        $rule = MaterialityDecisionRule::default();

        $this->assertSame(50, $rule->thresholdPercent);
        $this->assertTrue($rule->highMagnitudeForcesInclusion);
    }

    public function testThresholdZeroThrows(): void
    {
        $this->expectException(InvalidRelevanceThresholdException::class);

        new MaterialityDecisionRule(0, true);
    }

    public function testThresholdOverOneHundredThrows(): void
    {
        $this->expectException(InvalidRelevanceThresholdException::class);

        new MaterialityDecisionRule(101, true);
    }

    public function testScoreEqualToThresholdIsCalculated(): void
    {
        $this->assertSame(MaterialityVerdict::Calculate, MaterialityDecisionRule::default()->decide(50.0, null));
    }

    public function testScoreBelowThresholdIsOmitted(): void
    {
        $this->assertSame(MaterialityVerdict::Omit, MaterialityDecisionRule::default()->decide(49.99, null));
    }

    public function testHighMagnitudeForcesCalculation(): void
    {
        $rule = MaterialityDecisionRule::default();

        $this->assertSame(MaterialityVerdict::Calculate, $rule->decide(20.0, 3));
        $this->assertTrue($rule->isForcedByHighMagnitude(3));
    }

    public function testHighMagnitudeDoesNotForceWhenRuleIsDisabled(): void
    {
        $rule = new MaterialityDecisionRule(50, false);

        $this->assertSame(MaterialityVerdict::Omit, $rule->decide(20.0, 3));
        $this->assertFalse($rule->isForcedByHighMagnitude(3));
    }

    public function testMediumMagnitudeDoesNotForce(): void
    {
        $this->assertSame(MaterialityVerdict::Omit, MaterialityDecisionRule::default()->decide(20.0, 2));
    }

    public function testWithoutScoreThereIsNoVerdict(): void
    {
        $this->assertNull(MaterialityDecisionRule::default()->decide(null, 3));
    }
}
