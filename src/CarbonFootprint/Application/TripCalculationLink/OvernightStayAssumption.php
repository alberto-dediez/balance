<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculationLink;

use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\Property\Domain\Model\Property\Property;

final class OvernightStayAssumption
{
    public static function appliesTo(TripCalculationLink $tripCalculationLink, ?Property $property): bool
    {
        return null !== $property
            && $tripCalculationLink->isForProperty()
            && $tripCalculationLink->scope()->asksForBoth()
            && $property->info()->isAccommodation();
    }
}
