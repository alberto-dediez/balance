<?php

declare(strict_types=1);

namespace App\Balance\Domain\Model\ProjectContribution;

enum ProjectContributionOrigin: string
{
    case PropertyTripCalculation = 'property_trip_calculation';
}
