<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\AttendeeSurvey\GetStats;

use App\CarbonFootprint\Application\Contribution\ContributionsSummary;
use App\CarbonFootprint\Application\Trip\MobilityBreakdownRow;
use App\CarbonFootprint\Application\Trip\OriginFlow;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurvey;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Domain\Model\Geo\GeoPoint;

class GetAttendeeSurveyStatsResponse implements CommandResponse
{
    /**
     * @param array<string, int>         $legsByDistanceSource
     * @param list<MobilityBreakdownRow> $mobilityBreakdown
     * @param array<string, int>         $responsesByDay
     * @param array<string, int>         $topOrigins
     * @param list<OriginFlow>           $originFlows
     */
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
        public Emissions $totalEmissions = new Emissions(),
        public Emissions $averageEmissions = new Emissions(),
        public Emissions $emissionsPerPerson = new Emissions(),
        public Emissions $mobilityEmissions = new Emissions(),
        public Emissions $overnightEmissions = new Emissions(),
        public array $mobilityBreakdown = [],
        public array $responsesByDay = [],
        public array $topOrigins = [],
        public int $fromAnotherCityCount = 0,
        public int $usedHotelCount = 0,
        public int $movedWithinCityCount = 0,
        public int $totalNights = 0,
        public array $originFlows = [],
        public ?GeoPoint $destination = null,
        public int $unlocatedResponses = 0,
    ) {}
}
