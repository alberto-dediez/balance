<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ContestEntry\Remove;

use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryId;
use App\Shared\Application\CommandBus\Command;

class RemoveContestEntryRequest implements Command
{
    public function __construct(
        public ContestId $contestId,
        public ContestEntryId $contestEntryId,
    ) {}
}
