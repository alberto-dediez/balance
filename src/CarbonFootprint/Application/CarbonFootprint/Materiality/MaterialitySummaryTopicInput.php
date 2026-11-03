<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CarbonFootprint\Materiality;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityVerdict;

final readonly class MaterialitySummaryTopicInput
{
    public function __construct(
        public string $topicId,
        public ?int $topicNumber,
        public ?float $scorePercent,
        public ?int $thresholdPercent,
        public bool $forcedByHighMagnitude,
        public ?MaterialityVerdict $verdict,
    ) {}
}
