<?php

namespace App\Property\Application\Country\Update;

use App\Shared\Application\CommandBus\Command;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Property\ExternalId;

class UpdateCountryRequest implements Command
{
    public function __construct(public CountryId $countryId, public string $name, public ?string $iso, public ?ExternalId $externalId = null)
    {

    }
}