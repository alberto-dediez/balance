<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RoadTransport\EmissionFactor\CreateYear;

use App\Shared\Application\CommandBus\Command;

class CreateEmissionFactorsYearRequest implements Command
{
    public function __construct(
        public int $sourceYear,
        public ?string $emissionFactorGroupId = null,
        public ?int $targetYear = null,
    ) {}
}
