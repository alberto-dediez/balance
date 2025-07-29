<?php

namespace App\Property\Application\Destination\Register;

use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\ExternalId;

class RegisterDestinationRequest
{
    public function __construct(public string $name, public CountryId $countryId, public ?ExternalId $externalId = null)
    {
        
    }
}