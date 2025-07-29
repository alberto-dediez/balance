<?php

namespace App\Property\Application\Destination\Register;

use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Destination\DestinationId;

class RegisterDestinationResponse
{
    public function __construct(public DestinationId $destinationId, public string $name, public CountryId $countryId)
    {
        
    }
}