<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\ConfidenceLevel;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportEmployeeSurvey;
use PHPUnit\Framework\TestCase;

final class OrganizationalCarbonFootprintReportEmployeeSurveyTest extends TestCase
{
    public function testTheParticipationIsTheShareOfAnsweredEmployees(): void
    {
        $survey = new OrganizationalCarbonFootprintReportEmployeeSurvey(40, 25, ConfidenceLevel::NinetyFive, 0.065);

        $this->assertTrue($survey->hasSurvey());
        $this->assertEqualsWithDelta(62.5, $survey->participationPercent(), 0.001);
    }

    public function testThereIsNoSurveyWithoutAnswers(): void
    {
        $survey = new OrganizationalCarbonFootprintReportEmployeeSurvey(null, null, null, null);

        $this->assertFalse($survey->hasSurvey());
        $this->assertNull($survey->participationPercent());
    }
}
