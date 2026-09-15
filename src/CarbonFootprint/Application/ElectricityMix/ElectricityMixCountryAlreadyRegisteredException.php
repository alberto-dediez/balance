<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMix;

use App\Property\Domain\Model\Country\CountryId;

final class ElectricityMixCountryAlreadyRegisteredException extends \DomainException
{
    public static function forCountry(CountryId $countryId): self
    {
        return new self(sprintf('An electricity mix already exists for country %s', $countryId));
    }
}
