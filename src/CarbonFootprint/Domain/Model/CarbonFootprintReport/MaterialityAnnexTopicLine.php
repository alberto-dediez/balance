<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\ExclusionReason;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityCategoryState;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityVerdict;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;

final readonly class MaterialityAnnexTopicLine
{
    public function __construct(
        public ?string $topicId,
        public ?int $number,
        public ?string $name,
        public ?Source $source,
        public ?float $scorePercent,
        public ?int $thresholdPercent,
        public bool $forcedByHighMagnitude,
        public ?MaterialityVerdict $verdict,
        public MaterialityCategoryState $state,
        public ?ExclusionReason $exclusionReason,
        public ?string $exclusionComment,
        public ?string $overriddenByName,
        public ?\DateTimeImmutable $overriddenAt,
        public ?string $overrideJustification,
        public ?\DateTimeImmutable $recommendedAt,
    ) {}

    public function isExcluded(): bool
    {
        return in_array($this->state, [MaterialityCategoryState::ExcludedRecommended, MaterialityCategoryState::ExcludedOverridden], true);
    }
}
