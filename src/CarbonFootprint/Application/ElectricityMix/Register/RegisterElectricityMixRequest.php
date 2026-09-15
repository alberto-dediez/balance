<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMix\Register;

use App\Property\Domain\Model\Country\CountryId;
use App\Shared\Application\CommandBus\Command;

class RegisterElectricityMixRequest implements Command
{
    public function __construct(
        public ?CountryId $countryId = null,
    ) {}
}
