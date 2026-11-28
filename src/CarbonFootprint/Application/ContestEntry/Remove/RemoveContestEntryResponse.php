<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ContestEntry\Remove;

use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntry;
use App\Shared\Application\CommandBus\CommandResponse;

class RemoveContestEntryResponse implements CommandResponse
{
    public function __construct(
        public ContestEntry $contestEntry,
    ) {}
}
