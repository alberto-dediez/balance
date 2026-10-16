<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Transport\Update;

use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Transport\Vehicle;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Application\CommandBus\Command;

class UpdateTransportRequest implements Command
{
    /** @param EmissionFactorData[] $emissionFactors */
    public function __construct(
        public TransportId $transportId,
        public string $name,
        public Unit $unit,
        public Vehicle $vehicle,
        public array $emissionFactors = [],
    ) {}
}
