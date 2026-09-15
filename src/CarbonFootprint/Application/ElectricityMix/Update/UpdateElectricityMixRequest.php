<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMix\Update;

use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\Property\Domain\Model\Country\CountryId;
use App\Shared\Application\CommandBus\Command;

class UpdateElectricityMixRequest implements Command
{
    /** @param EmissionFactorData[] $emissionFactors */
    public function __construct(
        public ElectricityMixId $electricityMixId,
        public ?CountryId $countryId = null,
        public array $emissionFactors = [],
    ) {}
}
