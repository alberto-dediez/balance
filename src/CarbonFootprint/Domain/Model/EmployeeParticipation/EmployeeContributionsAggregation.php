<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\EmployeeParticipation;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;

final class EmployeeContributionsAggregation
{
    /**
     * @param array<string, array<string, float>> $kmByMobilityIdByPhase
     * @param array<string, float>                $hotelNightsByPhase
     */
    public function __construct(
        private readonly array $kmByMobilityIdByPhase,
        private readonly array $hotelNightsByPhase,
    ) {}

    /** @return array<string, float> */
    public function kmByMobilityId(Phase $phase): array
    {
        return $this->kmByMobilityIdByPhase[$phase->value] ?? [];
    }

    public function hotelNights(Phase $phase): float
    {
        return $this->hotelNightsByPhase[$phase->value] ?? 0.0;
    }
}
