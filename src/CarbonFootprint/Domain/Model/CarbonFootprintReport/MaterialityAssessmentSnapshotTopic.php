<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\CarbonFootprintReport;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityVerdict;

final readonly class MaterialityAssessmentSnapshotTopic
{
    public function __construct(
        public string $topicId,
        public ?int $topicNumber,
        public string $name,
        public ?int $points,
        public ?int $minPoints,
        public ?int $maxPoints,
        public ?float $scorePercent,
        public ?int $thresholdPercent,
        public bool $forcedByHighMagnitude,
        public ?MaterialityVerdict $verdict,
    ) {}
}
