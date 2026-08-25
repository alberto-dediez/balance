<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Mobility\Update;

use App\CarbonFootprint\Domain\Model\Geo\TravelMode;
use App\CarbonFootprint\Domain\Model\Mobility\FactorBasis;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Application\CommandBus\Command;

class UpdateMobilityRequest implements Command
{
    /** @param EmissionFactorData[] $emissionFactors */
    public function __construct(
        public MobilityId $mobilityId,
        public string $name,
        public Unit $unit,
        public ?TravelMode $travelMode = null,
        public FactorBasis $factorBasis = FactorBasis::PerPassenger,
        public array $emissionFactors = [],
    ) {}
}
