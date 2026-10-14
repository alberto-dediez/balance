<?php

declare(strict_types=1);

namespace App\Property\Application\Country\Remove;

use App\Property\Domain\Model\Country\CountryId;
use App\Shared\Application\CommandBus\Command;

class RemoveCountryRequest implements Command
{
    public function __construct(public CountryId $countryId) {}
}
