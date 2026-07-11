<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\AttendeeSurvey;

final class AttendeeSurveyAggregation
{
    /** @param array<string, float> $kmByMobilityId */
    public function __construct(
        public readonly array $kmByMobilityId,
        public readonly float $hotelNights,
    ) {}
}
