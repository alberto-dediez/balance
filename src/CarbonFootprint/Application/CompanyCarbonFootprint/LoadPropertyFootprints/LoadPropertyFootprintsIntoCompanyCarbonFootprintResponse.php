<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\LoadPropertyFootprints;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\Shared\Application\CommandBus\CommandResponse;

class LoadPropertyFootprintsIntoCompanyCarbonFootprintResponse implements CommandResponse
{
    /**
     * @param list<SkippedPropertyFootprint>    $skipped
     * @param list<PropertyEmissionsDifference> $differences
     * @param list<Source>                      $includedSources
     */
    public function __construct(
        public int $totalProperties,
        public int $copiedProperties = 0,
        public int $activities = 0,
        public array $skipped = [],
        public array $differences = [],
        public array $includedSources = [],
    ) {}

    public function isResolved(): bool
    {
        return $this->copiedProperties > 0;
    }
}
