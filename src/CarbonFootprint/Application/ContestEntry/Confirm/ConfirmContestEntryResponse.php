<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ContestEntry\Confirm;

use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntry;

class ConfirmContestEntryResponse
{
    /** @param list<ContestEntry> $entries */
    public function __construct(
        public array $entries = [],
    ) {}
}
