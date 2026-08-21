<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport;

final class EventCarbonFootprintReportTexts
{
    public function __construct(
        public readonly string $executiveSummary,
        public readonly string $methodology,
        public readonly string $emissionFactorSources,
        public readonly string $analysisAndRecommendations,
        public readonly string $compensation = '',
    ) {}
}
