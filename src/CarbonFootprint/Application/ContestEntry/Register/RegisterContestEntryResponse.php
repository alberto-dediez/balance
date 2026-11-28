<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ContestEntry\Register;

use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntry;

class RegisterContestEntryResponse
{
    public function __construct(
        public ContestEntry $entry,
    ) {}
}
