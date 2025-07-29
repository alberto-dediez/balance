<?php

namespace App\Property\Application\Country\Update;

use App\Property\Domain\Model\Country\CountryId;

class UpdateCountryResponse
{
    public function __construct(public CountryId $countryId, public string $name, public ?string $iso)
    {
        
    }
}