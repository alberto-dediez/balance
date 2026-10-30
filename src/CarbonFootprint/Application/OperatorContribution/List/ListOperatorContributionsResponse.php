<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OperatorContribution\List;

use App\CarbonFootprint\Application\OperatorContribution\OperatorContributionSummary;

final class ListOperatorContributionsResponse
{
    /** @param array<string, OperatorContributionSummary> $summariesByProperty */
    public function __construct(
        public readonly array $summariesByProperty,
    ) {}
}
