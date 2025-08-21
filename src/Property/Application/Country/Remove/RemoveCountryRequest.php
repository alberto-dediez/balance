<?php

namespace App\Property\Application\Country\Remove;

use App\Shared\Application\CommandBus\Command;
use App\Property\Domain\Model\Country\CountryId;

class RemoveCountryRequest implements Command
{
    public function __construct(public CountryId $countryId)
    {

    }
}