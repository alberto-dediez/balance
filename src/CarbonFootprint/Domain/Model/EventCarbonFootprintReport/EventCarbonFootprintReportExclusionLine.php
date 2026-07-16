<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Source;

final class EventCarbonFootprintReportExclusionLine
{
    public function __construct(
        public readonly Phase $phase,
        public readonly Source $source,
        public readonly ExclusionReason $reason,
        public readonly ?string $comment = null,
    ) {}
}
