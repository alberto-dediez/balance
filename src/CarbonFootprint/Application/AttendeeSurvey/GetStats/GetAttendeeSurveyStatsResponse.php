<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\AttendeeSurvey\GetStats;

use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurvey;
use App\Shared\Application\CommandBus\CommandResponse;

class GetAttendeeSurveyStatsResponse implements CommandResponse
{
    public function __construct(
        public AttendeeSurvey $attendeeSurvey,
        public int $responsesCount,
        public ?int $estimatedAttendees,
        public ?float $surveyedPercentage,
    ) {}
}
