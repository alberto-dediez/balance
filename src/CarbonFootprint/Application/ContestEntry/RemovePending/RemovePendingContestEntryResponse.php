<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ContestEntry\RemovePending;

class RemovePendingContestEntryResponse
{
    public function __construct(
        public int $removed = 0,
    ) {}
}
