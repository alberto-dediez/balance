<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\LoadPropertyFootprints;

enum PropertyFootprintSkipReason: string
{
    case NoCompletedFootprint = 'no_completed_footprint';
    case Precalculated = 'precalculated';
    case NoActivities = 'no_activities';
}
