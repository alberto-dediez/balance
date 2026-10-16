<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EconomicSector\Update;

use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Application\CommandBus\Command;

class UpdateEconomicSectorRequest implements Command
{
    /** @param EmissionFactorData[] $emissionFactors */
    public function __construct(
        public EconomicSectorId $economicSectorId,
        public string $name,
        public Unit $unit,
        public ?string $classificationCode = null,
        public array $emissionFactors = [],
    ) {}
}
