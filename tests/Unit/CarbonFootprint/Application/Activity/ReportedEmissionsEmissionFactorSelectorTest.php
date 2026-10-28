<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Application\Activity\ReportedEmissionsEmissionFactorSelector;
use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use PHPUnit\Framework\TestCase;

class ReportedEmissionsEmissionFactorSelectorTest extends TestCase
{
    public function testActivityTypeIsReportedEmissions(): void
    {
        $this->assertSame(ActivityType::ReportedEmissions, (new ReportedEmissionsEmissionFactorSelector())->activityType());
    }

    public function testSelectReturnsIdentityFactorForYearAndGroup(): void
    {
        $groupId = EmissionFactorGroupMother::random()->id();

        $factor = (new ReportedEmissionsEmissionFactorSelector())->select('ignored', 2025, null, $groupId);

        $this->assertSame('1', $factor->co2eq());
        $this->assertSame('1', $factor->co2());
        $this->assertSame('0', $factor->ch4());
        $this->assertSame('0', $factor->n2o());
        $this->assertSame(2025, $factor->year());
        $this->assertSame(Unit::Kilogram, $factor->unit());
        $this->assertSame('reported', $factor->source());
        $this->assertTrue($groupId->equals($factor->emissionFactorGroupId()));
    }
}
