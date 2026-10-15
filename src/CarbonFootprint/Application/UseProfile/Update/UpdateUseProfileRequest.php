<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\UseProfile\Update;

use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\Shared\Application\CommandBus\Command;

class UpdateUseProfileRequest implements Command
{
    /** @param EmissionFactorData[] $emissionFactors */
    public function __construct(
        public UseProfileId $useProfileId,
        public string $name,
        public Unit $unit,
        public array $emissionFactors = [],
    ) {}
}
