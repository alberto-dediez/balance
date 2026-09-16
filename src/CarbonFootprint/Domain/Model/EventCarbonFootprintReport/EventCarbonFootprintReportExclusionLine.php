<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;

final class EventCarbonFootprintReportExclusionLine
{
    public function __construct(
        public readonly Phase $phase,
        public readonly Source $source,
        public readonly ExclusionReason $reason,
        public readonly ?string $comment = null,
    ) {}
}
