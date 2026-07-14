<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport;

final class EventCarbonFootprintReportCharts
{
    public function __construct(
        public readonly string $bySource,
        public readonly string $byPhase,
        public readonly string $byPhaseAndSource,
    ) {}
}
