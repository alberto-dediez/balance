<?php

namespace App\Property\Application\Destination\Update;

use App\Property\Domain\Model\Country\CountryId;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Property\Domain\Model\Destination\DestinationId;

class UpdateDestinationResponse implements CommandResponse
{
    public function __construct(public DestinationId $destinationId, public string $name, public CountryId $countryId)
    {
        
    }
}