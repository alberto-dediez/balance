<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprintReport\Data;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReport;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('carbon_footprint.report_data_collector')]
interface CarbonFootprintReportDataCollector
{
    public function supports(CarbonFootprintReportSubjectType $subjectType): bool;

    public function collect(CarbonFootprintReport $report): CarbonFootprintReportData;
}
