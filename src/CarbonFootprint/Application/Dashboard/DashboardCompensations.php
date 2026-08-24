<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Dashboard;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionsAggregation;

final readonly class DashboardCompensations
{
    /**
     * @param array<int, float>    $compensatedByYear co2eq (kg) indexed by payment year
     * @param array<string, float> $fundsByProject    amount (€) indexed by project name
     */
    public function __construct(
        public ProjectContributionsAggregation $aggregation,
        public float $compensatedCo2eqKg,
        public float $periodCompensatedCo2eqKg,
        public float $fundsAmount,
        public float $periodFundsAmount,
        public float $socialFundsAmount,
        public float $voluntaryCompensatedCo2eqKg,
        public float $ownCompensatedCo2eqKg,
        public int $voluntaryPaidContributions,
        public int $voluntaryContributors,
        public int $attendeeSurveys,
        public int $surveyResponses,
        public int $tripCalculationLinks,
        public int $tripCalculations,
        public float $voluntaryParticipationRate,
        public array $compensatedByYear,
        public array $fundsByProject,
    ) {}
}
