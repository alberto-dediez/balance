<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

enum CarbonFootprintReportSubjectType: string
{
    case Event    = 'event';
    case Property = 'property';
    case Company  = 'company';
}
