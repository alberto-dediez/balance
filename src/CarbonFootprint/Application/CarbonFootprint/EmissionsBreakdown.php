<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprint;

use App\CarbonFootprint\Domain\Model\Emissions;

final class EmissionsBreakdown
{
    /**
     * @param array<string, Emissions>                $bySource
     * @param array<string, Emissions>                $byStage
     * @param array<string, array<string, Emissions>> $byStageAndSource
     * @param array<string, Emissions>                $byPropertyId
     */
    public function __construct(
        public readonly Emissions $total,
        public readonly array $bySource,
        public readonly array $byStage,
        public readonly array $byStageAndSource,
        public readonly array $byPropertyId,
        public readonly Emissions $locationBasedElectricity,
    ) {}
}
