<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer;

use App\CarbonFootprint\Domain\Model\Activity\ActivityType;
use App\CarbonFootprint\Domain\Model\Activity\Answer\ReportedEmissionsAnswer;
use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Infrastructure\UI\Web\EventCarbonFootprint\Step\Answer\ReportedEmissionsAnswerHttpMapper;
use PHPUnit\Framework\TestCase;

class ReportedEmissionsAnswerHttpMapperTest extends TestCase
{
    private ReportedEmissionsAnswerHttpMapper $sut;

    protected function setUp(): void
    {
        $this->sut = new ReportedEmissionsAnswerHttpMapper();
    }

    public function testActivityTypeIsReportedEmissions(): void
    {
        $this->assertSame(ActivityType::ReportedEmissions, $this->sut->activityType());
    }

    public function testMapsAValidRowToATypedAnswer(): void
    {
        $answer = $this->sut->map(['description' => ' Reparto 2025 ', 'unit' => 'kg', 'quantity' => '3200', 'activityId' => 'activity-1']);

        $this->assertInstanceOf(ReportedEmissionsAnswer::class, $answer);
        $this->assertSame('Reparto 2025', $answer->description);
        $this->assertSame(Unit::Kilogram, $answer->quantity->unit());
        $this->assertSame(3200.0, $answer->quantity->value());
        $this->assertNotNull($answer->existingActivityId);
        $this->assertSame('activity-1', (string) $answer->existingActivityId);
    }

    public function testAcceptsTonnesWithDecimalComma(): void
    {
        $answer = $this->sut->map(['description' => 'Última milla', 'unit' => 't', 'quantity' => '0,8']);

        $this->assertInstanceOf(ReportedEmissionsAnswer::class, $answer);
        $this->assertSame(Unit::Tonne, $answer->quantity->unit());
        $this->assertSame(0.8, $answer->quantity->value());
        $this->assertNull($answer->existingActivityId);
    }

    public function testReturnsNullWhenDescriptionIsEmpty(): void
    {
        $this->assertNull($this->sut->map(['description' => '   ', 'unit' => 'kg', 'quantity' => '10']));
    }

    public function testReturnsNullWhenUnitIsNotKilogramOrTonne(): void
    {
        $this->assertNull($this->sut->map(['description' => 'Reparto', 'unit' => 'g', 'quantity' => '10']));
        $this->assertNull($this->sut->map(['description' => 'Reparto', 'unit' => 'l', 'quantity' => '10']));
    }

    public function testReturnsNullWhenQuantityIsNegative(): void
    {
        $this->assertNull($this->sut->map(['description' => 'Reparto', 'unit' => 'kg', 'quantity' => '-5']));
    }
}
