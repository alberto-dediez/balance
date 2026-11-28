<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ContestEntry\Disqualify;

use App\CarbonFootprint\Domain\Model\Contest\ContestId;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryId;
use App\Shared\Application\CommandBus\Command;

class DisqualifyContestEntryRequest implements Command
{
    public function __construct(
        public ContestId $contestId,
        public ContestEntryId $contestEntryId,
        public string $reason = '',
    ) {}
}
