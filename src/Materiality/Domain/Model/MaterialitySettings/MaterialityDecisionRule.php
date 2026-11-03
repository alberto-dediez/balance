<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialitySettings;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityVerdict;
use App\Materiality\Domain\Model\MaterialityQuestion\MaterialityAnswerScale;

final readonly class MaterialityDecisionRule
{
    public const int DEFAULT_THRESHOLD_PERCENT = 50;

    public function __construct(
        public int $thresholdPercent,
        public bool $highMagnitudeForcesInclusion,
    ) {
        if ($thresholdPercent < 1 || $thresholdPercent > 100) {
            throw new InvalidRelevanceThresholdException($thresholdPercent);
        }
    }

    public static function default(): self
    {
        return new self(self::DEFAULT_THRESHOLD_PERCENT, true);
    }

    public function decide(?float $scorePercent, ?int $highestMagnitudeLevel): ?MaterialityVerdict
    {
        if (null === $scorePercent) {
            return null;
        }

        return $scorePercent >= $this->thresholdPercent || $this->isForcedByHighMagnitude($highestMagnitudeLevel)
            ? MaterialityVerdict::Calculate
            : MaterialityVerdict::Omit;
    }

    public function isForcedByHighMagnitude(?int $highestMagnitudeLevel): bool
    {
        return $this->highMagnitudeForcesInclusion && MaterialityAnswerScale::Level->max() === $highestMagnitudeLevel;
    }
}
