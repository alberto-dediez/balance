<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Activity;

use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\Emissions;

final class ElectricityMixEmissions
{
    public function __construct(
        public ElectricityMixId $electricityMixId,
        public EmissionFactor $emissionFactor,
        public Emissions $emissions,
    ) {}
}
