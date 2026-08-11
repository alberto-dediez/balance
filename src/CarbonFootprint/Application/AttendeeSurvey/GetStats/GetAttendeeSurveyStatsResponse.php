<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\AttendeeSurvey\GetStats;

use App\CarbonFootprint\Application\Contribution\ContributionsSummary;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurvey;
use App\Shared\Application\CommandBus\CommandResponse;

class GetAttendeeSurveyStatsResponse implements CommandResponse
{
    /** @param array<string, int> $legsByDistanceSource */
    public function __construct(
        public AttendeeSurvey $attendeeSurvey,
        public int $responsesCount,
        public ?int $estimatedAttendees,
        public ?float $surveyedPercentage,
        public ContributionsSummary $contributions,
        public bool $venueLocated = false,
        public ?string $venueLabel = null,
        public array $legsByDistanceSource = [],
        public int $multimodalResponsesCount = 0,
    ) {}
}
