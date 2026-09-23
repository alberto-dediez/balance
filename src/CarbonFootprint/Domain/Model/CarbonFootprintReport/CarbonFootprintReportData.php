<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\Emissions;

interface CarbonFootprintReportData
{
    public function subjectType(): CarbonFootprintReportSubjectType;

    public function subjectName(): string;

    public function calculationYear(): int;

    public function totalEmissions(): Emissions;
}
