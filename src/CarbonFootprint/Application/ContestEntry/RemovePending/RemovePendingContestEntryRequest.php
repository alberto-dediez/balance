<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ContestEntry\RemovePending;

class RemovePendingContestEntryRequest
{
    public function __construct(
        public string $projectContributionId,
    ) {}
}
