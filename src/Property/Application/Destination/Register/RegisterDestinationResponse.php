<?php

namespace App\Property\Application\Destination\Register;

use App\Property\Domain\Model\Country\CountryId;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Property\Domain\Model\Destination\DestinationId;

class RegisterDestinationResponse implements CommandResponse
{
    public function __construct(public DestinationId $destinationId, public string $name, public CountryId $countryId)
    {
        
    }
}