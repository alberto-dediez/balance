<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMarketer\Update;

use App\CarbonFootprint\Domain\Model\ElectricityMarketer\ElectricityMarketerId;
use App\Shared\Application\CommandBus\Command;

class UpdateElectricityMarketerRequest implements Command
{
    /** @param EmissionFactorData[] $emissionFactors */
    public function __construct(
        public ElectricityMarketerId $electricityMarketerId,
        public string $name,
        public array $emissionFactors = [],
    ) {}
}
