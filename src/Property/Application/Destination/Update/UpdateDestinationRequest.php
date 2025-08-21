<?php

namespace App\Property\Application\Destination\Update;

use App\Shared\Application\CommandBus\Command;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\ExternalId;
use App\Property\Domain\Model\Destination\DestinationId;

class UpdateDestinationRequest implements Command
{
    public function __construct(public DestinationId $destinationId, public string $name, public CountryId $countryId, public ?ExternalId $externalId = null)
    {

    }
}