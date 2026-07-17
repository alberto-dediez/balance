<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EmployeeContribution\AnswerPhase;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;

final class AnswerEmployeeContributionPhaseRequest
{
    public function __construct(
        #[\SensitiveParameter]
        public readonly string $token,
        public readonly Phase $phase,
        public readonly bool $worked,
        public readonly ?MobilityId $mobilityId,
        public readonly ?float $distanceKm,
        public readonly ?int $vehicleOccupants,
        public readonly ?int $days,
        public readonly ?int $hotelNights,
    ) {}
}
