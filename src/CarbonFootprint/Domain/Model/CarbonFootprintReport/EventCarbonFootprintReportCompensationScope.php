<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

enum EventCarbonFootprintReportCompensationScope: string
{
    case OwnCompany = 'own_company';

    case Attendees = 'attendees';
}
