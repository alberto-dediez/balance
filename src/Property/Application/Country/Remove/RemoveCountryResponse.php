<?php

namespace App\Property\Application\Country\Remove;

use App\Property\Domain\Model\Country\CountryId;

class RemoveCountryResponse
{
    public function __construct(public CountryId $countryId)
    {
        
    }
}