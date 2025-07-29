<?php

namespace App\Property\Application\Country\Register;

use App\Property\Domain\Model\Country\CountryId;

class RegisterCountryResponse
{
    public function __construct(public CountryId $countryId, public string $name, public ?string $iso = null)
    {
        
    }
}