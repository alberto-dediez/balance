<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprint\Materiality;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecision;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityVerdict;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\SourceExclusion;

final readonly class MaterialitySummaryRow
{
    public function __construct(
        public ?string $topicId,
        public ?int $topicNumber,
        public ?float $scorePercent,
        public ?int $thresholdPercent,
        public bool $forcedByHighMagnitude,
        public ?MaterialityVerdict $verdict,
        public ?Source $source,
        public MaterialityCategoryState $state,
        public ?SourceExclusion $exclusion,
        public ?MaterialityDecision $decision,
    ) {}
}
