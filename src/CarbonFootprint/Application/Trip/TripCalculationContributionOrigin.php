<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Trip;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;

final class TripCalculationContributionOrigin
{
    public static function for(TripCalculationLink $tripCalculationLink): ProjectContributionOrigin
    {
        return $tripCalculationLink->isForProperty()
            ? ProjectContributionOrigin::PropertyTripCalculation
            : ProjectContributionOrigin::TripCalculation;
    }
}
