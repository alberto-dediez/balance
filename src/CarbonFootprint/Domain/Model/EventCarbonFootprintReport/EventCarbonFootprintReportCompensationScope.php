<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport;

enum EventCarbonFootprintReportCompensationScope: string
{
    case OwnCompany = 'own_company';

    case Attendees = 'attendees';
}
