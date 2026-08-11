<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\TripCalculation\GetResults;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionsAggregation;

final readonly class ContributionsSummary
{
    public function __construct(
        public ProjectContributionsAggregation $aggregation,
        public float $startedRate,
        public float $paidRate,
        public float $completionRate,
        public float $compensatedEmissionsRate,
    ) {}
}
