<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ContestEntry\CheckEligibility;

use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntry;

class CheckContestEntryEligibilityResponse
{
    public function __construct(
        public Contest $contest,
        public ?ContestEntry $pendingEntry = null,
    ) {}
}
