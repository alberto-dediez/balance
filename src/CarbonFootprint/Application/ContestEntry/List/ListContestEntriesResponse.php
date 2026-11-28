<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ContestEntry\List;

use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntry;

class ListContestEntriesResponse
{
    /** @param list<ContestEntry> $entries */
    public function __construct(
        public Contest $contest,
        public array $entries = [],
        public int $totalResults = 0,
        public int $disqualified = 0,
        public int $winners = 0,
    ) {}
}
