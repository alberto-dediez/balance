<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMix\Update;

use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\Property\Domain\Model\Country\CountryId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateElectricityMixResponse implements CommandResponse
{
    public function __construct(
        public ElectricityMixId $electricityMixId,
        public CountryId $countryId,
    ) {}
}
