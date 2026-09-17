<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreConsumption\LoadCompanyCarbonFootprintConsumptions;

use App\Bioscore\Application\BioscoreConsumption\SkippedBioscoreConsumption;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\Shared\Application\CommandBus\CommandResponse;

class LoadCompanyCarbonFootprintConsumptionsResponse implements CommandResponse
{
    /**
     * @param list<SkippedBioscoreConsumption> $skipped
     * @param list<Source>                     $includedSources
     */
    public function __construct(
        public int $totalProperties,
        public int $resolvedProperties = 0,
        public int $activities = 0,
        public array $skipped = [],
        public array $includedSources = [],
    ) {}

    public function isResolved(): bool
    {
        return $this->resolvedProperties > 0;
    }
}
