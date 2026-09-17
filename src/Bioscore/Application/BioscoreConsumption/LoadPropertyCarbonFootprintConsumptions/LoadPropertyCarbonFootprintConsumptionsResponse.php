<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreConsumption\LoadPropertyCarbonFootprintConsumptions;

use App\Bioscore\Application\BioscoreConsumption\SkippedBioscoreConsumption;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\Shared\Application\CommandBus\CommandResponse;

class LoadPropertyCarbonFootprintConsumptionsResponse implements CommandResponse
{
    /**
     * @param list<SkippedBioscoreConsumption> $skipped
     * @param list<Source>                     $includedSources
     */
    public function __construct(
        public bool $resolved,
        public int $activities = 0,
        public array $skipped = [],
        public array $includedSources = [],
    ) {}
}
