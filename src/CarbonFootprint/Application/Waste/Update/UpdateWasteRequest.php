<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Waste\Update;

use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\Waste\WasteId;
use App\Shared\Application\CommandBus\Command;

class UpdateWasteRequest implements Command
{
    /** @param EmissionFactorData[] $emissionFactors */
    public function __construct(
        public WasteId $wasteId,
        public string $name,
        public Unit $unit,
        public float $density = 1.0,
        public array $emissionFactors = [],
    ) {}
}
