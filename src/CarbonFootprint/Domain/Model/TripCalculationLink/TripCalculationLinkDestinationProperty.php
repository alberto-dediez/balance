<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculationLink;

use App\Property\Domain\Model\Property\PropertyId;

class TripCalculationLinkDestinationProperty
{
    public function __construct(
        private TripCalculationLinkDestinationPropertyId $id,
        private TripCalculationLink $tripCalculationLink,
        private PropertyId $propertyId,
    ) {}

    public function id(): TripCalculationLinkDestinationPropertyId
    {
        return $this->id;
    }

    public function tripCalculationLink(): TripCalculationLink
    {
        return $this->tripCalculationLink;
    }

    public function propertyId(): PropertyId
    {
        return $this->propertyId;
    }
}
