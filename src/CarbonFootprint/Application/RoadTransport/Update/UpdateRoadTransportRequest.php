<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RoadTransport\Update;

use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\CarbonFootprint\Domain\Model\RoadTransport\Vehicle;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Application\CommandBus\Command;

class UpdateRoadTransportRequest implements Command
{
    /** @param EmissionFactorData[] $emissionFactors */
    public function __construct(
        public RoadTransportId $roadTransportId,
        public string $name,
        public Unit $unit,
        public Vehicle $vehicle,
        public array $emissionFactors = [],
    ) {}
}
