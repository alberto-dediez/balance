<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\GenerateTexts;

use App\CarbonFootprint\Application\CarbonFootprintReport\Data\CarbonFootprintReportDataCollector;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportDataMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportDataMother;

class StubCarbonFootprintReportDataCollector implements CarbonFootprintReportDataCollector
{
    public function supports(CarbonFootprintReportSubjectType $subjectType): bool
    {
        return true;
    }

    public function collect(CarbonFootprintReport $report): CarbonFootprintReportData
    {
        return match ($report->subjectType()) {
            CarbonFootprintReportSubjectType::Event => EventCarbonFootprintReportDataMother::random(),
            CarbonFootprintReportSubjectType::Property => OrganizationalCarbonFootprintReportDataMother::forProperty(),
            CarbonFootprintReportSubjectType::Company => OrganizationalCarbonFootprintReportDataMother::forCompanyWithTwoProperties(),
        };
    }
}
