<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\TripCalculationLink;

use App\Property\Domain\Model\Country\CountryId;

class TripCalculationLinkDestinationCountry
{
    public function __construct(
        private TripCalculationLinkDestinationCountryId $id,
        private TripCalculationLink $tripCalculationLink,
        private CountryId $countryId,
    ) {}

    public function id(): TripCalculationLinkDestinationCountryId
    {
        return $this->id;
    }

    public function tripCalculationLink(): TripCalculationLink
    {
        return $this->tripCalculationLink;
    }

    public function countryId(): CountryId
    {
        return $this->countryId;
    }
}
