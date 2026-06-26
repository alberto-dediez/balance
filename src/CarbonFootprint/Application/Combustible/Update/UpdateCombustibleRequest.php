<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Combustible\Update;

use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Application\CommandBus\Command;

class UpdateCombustibleRequest implements Command
{
    /** @param EmissionFactorData[] $emissionFactors */
    public function __construct(
        public CombustibleId $combustibleId,
        public string $name,
        public Unit $unit,
        public array $emissionFactors = [],
    ) {}
}
