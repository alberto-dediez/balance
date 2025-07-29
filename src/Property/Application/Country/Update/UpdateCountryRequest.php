<?php

namespace App\Property\Application\Country\Update;

use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\ExternalId;

class UpdateCountryRequest
{
    public function __construct(public CountryId $countryId, public string $name, public ?string $iso, public ?ExternalId $externalId = null)
    {

    }
}